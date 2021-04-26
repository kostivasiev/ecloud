<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CapacityChange extends Job
{
    use Batchable;

    private $model;

    public function __construct(Volume $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $volume = $this->model;

        // Volume has no instances so can be resized freely
        $endpoint = '/api/v2/vpc/' . $volume->vpc_id . '/volume/' . $volume->vmware_uuid . '/size';

        if ($volume->instances()->count() > 0) {
            // Volume has at least one instance so needs to be resized via an instance, allowing it to expand the OS partitions AFAIK
            // TODO :- Need to confirm this doesn't break other instances using this volume, Spotts was going to investigate this
            $instance = $volume->instances()->first();
            $endpoint = '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/size';
        }

        try {
            $response = $volume->availabilityZone->kingpinService()->put(
                $endpoint,
                [
                    'json' => [
                        'sizeGiB' => $volume->capacity,
                    ],
                ]
            );
        } catch (ServerException $exception) {
            $response = $exception->getResponse();
        }

        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $volume->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents(),
            ]);
            $this->fail(new \Exception('Volume ' . $volume->id . ' failed to increase capacity to ' . $volume->capacity));
            return false;
        }

        Log::debug('Volume ' . $volume->id . ' capacity increased to ' . $volume->capacity);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
