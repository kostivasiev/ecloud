<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
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
        Log::info('Starting PrepareOsDisk for instance ' . $this->data['instance_id']);
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
        try {
            $volume = $instance->volumes->first();
            $volume->capacity = $this->data['volume_capacity'];
            $volume->save();

            // TODO - Move to "volume.updated"
            $response = $instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/size',
                [
                    'json' => [
                        'sizeGiB' => $this->data['volume_capacity'],
                    ]
                ]
            );

            if ($response->getStatusCode() != 200) {
                $message = 'Failed PrepareOsDisk for ' . $instance->id;
                Log::error($message, ['response' => $response]);
                $this->fail(new \Exception($message));
                return;
            }
        } catch (GuzzleException $exception) {
            $message = 'Failed PrepareOsDisk for ' . $instance->id;
            Log::error($message, ['exception' => $exception]);
            $this->fail(new \Exception($message));
            return;
        }

        // Extend to expanded size
        $endpoint = ($instance->platform == 'Linux') ? 'linux/disk/lvm/extend' : 'windows/disk/expandall';
        try {
            /** @var Response $response */
            $response = $instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/guest/' . $endpoint,
                [
                    'json' => [
                        'username' => $guestAdminCredential->username,
                        'password' => $guestAdminCredential->password,
                    ],
                ]
            );

            if ($response->getStatusCode() != 200) {
                $message = 'Failed PrepareOsDisk for ' . $instance->id;
                Log::error($message, ['response' => $response]);
                $this->fail(new \Exception($message));
                return;
            }
        } catch (GuzzleException $exception) {
            $message = 'Failed PrepareOsDisk for ' . $instance->id;
            Log::error($message, ['exception' => $exception]);
            $this->fail(new \Exception($message));
            return;
        }

        Log::info('PrepareOsDisk finished successfully for instance ' . $instance->id);
    }
}
