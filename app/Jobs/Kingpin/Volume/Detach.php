<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class Detach extends Job
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

        try {
            $this->instance->availabilityZone->kingpinService()
                ->post(
                    '/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->id . '/volume/detach',
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
        Log::debug('Volume ' . $this->volume->id . ' has been detached from instance ' . $this->instance->id);

        Log::info(get_class($this) . ' : Finished');
    }

    public function failed($exception)
    {
        $this->instance->setSyncFailureReason($exception->getMessage());
        $this->volume->setSyncFailureReason($exception->getMessage());
    }
}
