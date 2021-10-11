<?php

namespace App\Jobs\Kingpin\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DetachVolume extends Job
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
        try {
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

            if ($attached) {
                $this->instance->availabilityZone->kingpinService()
                    ->post('/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->id . '/volume/' . $this->volume->vmware_uuid . '/detach');
                Log::debug('Volume ' . $this->volume->id . ' has been detached from instance ' . $this->instance->id);
            } else {
                Log::warning('Volume isn\'t attached to instance, nothing to do');
            }
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
        }
        $this->instance->volumes()->detach($this->volume);
    }
}
