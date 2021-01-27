<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExpandGuest extends Job
{
    const RETRY_DELAY = 5;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil(): \DateTime
    {
        return Carbon::now()->addMinutes(5);
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/332
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);
        $volume = $instance->volumes->first();

        if ($volume->getStatus() != 'complete') {
            $this->release(static::RETRY_DELAY);
            Log::info(get_class($this) . ' : primary volume is not in sync, retrying in ' . static::RETRY_DELAY . ' seconds');
            return;
        }

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

        // Extend volume to expanded size
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
