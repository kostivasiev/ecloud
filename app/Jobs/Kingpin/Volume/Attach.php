<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Attach extends Job
{
    use Batchable;

    private Volume $volume;
    private Instance $instance;

    public function __construct(Volume $volume, Instance $instance)
    {
        $this->volume = $volume;
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started');

        if ($this->instance->volumes()->get()->count() > config('volume.instance.limit', 15)) {
            $this->fail(new \Exception(
                'Volume ' . $this->volume->id . ' failed to attach to instance ' .
                $this->instance->id . ', volume limit exceeded'
            ));
            return false;
        }

        $response = $this->instance->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id
            );

        $json = json_decode($response->getBody()->getContents());
        if (!$json) {
            $this->fail(new \Exception('Volume ' . $this->volume->id . ' failed attachment, invalid JSON'));
            return false;
        }

        foreach ($json->volumes as $volume) {
            if ($this->volume->vmware_uuid == $volume->uuid) {
                Log::info('Volume is already attached to instance, nothing to do');
                return true;
            }
        }

        $this->instance->availabilityZone->kingpinService()
            ->post(
                '/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->id . '/volume/attach',
                [
                    'json' => [
                        'volumeUUID' => $this->volume->vmware_uuid
                    ]
                ]
        );

        $this->instance->volumes()->attach($this->volume);

        Log::debug('Volume ' . $this->volume->id . ' has been attached to instance ' . $this->instance->id);

        Log::info(get_class($this) . ' : Finished');
    }
}
