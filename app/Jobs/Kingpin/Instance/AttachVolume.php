<?php

namespace App\Jobs\Kingpin\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AttachVolume extends Job
{
    use Batchable, LoggableModelJob;

    private Instance $instance;
    private Volume $volume;

    public function __construct(Instance $instance, Volume $volume)
    {
        $this->instance = $instance;
        $this->volume = $volume;
    }

    public function resolveModelId()
    {
        return $this->instance->id;
    }

    public function handle()
    {
        if ($this->instance->volumes()->get()->count() >= config('volume.instance.limit', 15)) {
            $this->fail(new \Exception(
                'Failed to attach volume ' . $this->volume->id . '  to instance ' .
                $this->instance->id . ', volume limit exceeded'
            ));
            return false;
        }

        $response = $this->instance->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id
        );

        $json = json_decode($response->getBody()->getContents());
        if (!$json) {
            throw new \Exception('Failed to retrieve instance ' . $this->instance->id . ' from Kingpin, invalid JSON');
        }

        $attached = false;
        foreach ($json->volumes as $volume) {
            if ($this->volume->vmware_uuid == $volume->uuid) {
                $attached = true;
                break;
            }
        }

        if (!$attached) {
            $this->instance->availabilityZone->kingpinService()
                ->post(
                    '/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->id . '/volume/attach',
                    [
                        'json' => [
                            'volumeUUID' => $this->volume->vmware_uuid
                        ]
                    ]
                );
        } else {
            Log::info('Volume is already attached to instance, nothing to do');
        }

        $this->instance->volumes()->attach($this->volume);

        Log::debug('Volume ' . $this->volume->id . ' has been attached to instance ' . $this->instance->id);
    }
}
