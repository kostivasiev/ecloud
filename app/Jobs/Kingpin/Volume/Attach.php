<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class Attach extends Job
{
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

        if ($this->instance->volumes()->get()->count() >= config('volume.instance.limit', 15)) {
            $this->fail(new \Exception(
                'Volume ' . $this->volume->id . ' failed to attach to instance ' .
                $this->instance->id . ', volume limit exceeded'
            ));
            return;
        }

        try {
            $this->instance->availabilityZone->kingpinService()
                ->post(
                    '/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->id . '/volume/attach',
                    [
                        'json' => [
                            'volumeUUID' => $this->volume->vmware_uuid
                        ]
                    ]
                );
        } catch (ServerException $exception) {
            Log::error($exception->getResponse()->getBody()->getContents());
            throw $exception;
        }
        Log::debug('Volume ' . $this->volume->id . ' has been attached to instance ' . $this->instance->id);

        Log::info(get_class($this) . ' : Finished');
    }

    public function failed($exception)
    {
        $this->instance->setSyncFailureReason($exception->getMessage());
        $this->volume->setSyncFailureReason($exception->getMessage());
    }
}
