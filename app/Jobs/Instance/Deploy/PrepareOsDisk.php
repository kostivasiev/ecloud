<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Listeners\V2\Volume\IopsChange;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class PrepareOsDisk extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/332
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $guestAdminCredential = $instance->credentials()
            ->where('username', ($instance->platform == 'Linux') ? 'root' : 'graphite.rack')
            ->firstOrFail();
        if (!$guestAdminCredential) {
            $message = 'PrepareOsDisk failed for ' . $instance->id . ', no admin credentials found';
            Log::error($message);
            $this->fail(new \Exception($message));
            return;
        }

        // Expand disk - Single volume for MVP
        // Todo: we need a better way of doing this, but we don't want the capacity increase listener to trigger here
        $volume = Volume::withoutEvents(function () use ($instance) {
            $volume = $instance->volumes->first();
            $volume->capacity = $this->data['volume_capacity'];
            $volume->save();
            $volume->setSyncCompleted();
            return $volume;
        });

        // Expand the volume
        $instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/size',
            [
                'json' => [
                    'sizeGiB' => $this->data['volume_capacity'],
                ]
            ]
        );

        // Extend to expanded size
        $endpoint = ($instance->platform == 'Linux') ? 'linux/disk/lvm/extend' : 'windows/disk/expandall';
        $instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/' . $endpoint,
            [
                'json' => [
                    'username' => $guestAdminCredential->username,
                    'password' => $guestAdminCredential->password,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
