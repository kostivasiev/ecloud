<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class IopsChange extends Job
{
    private $model;

    public function __construct(Volume $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $volume = $this->model;

        if (!$volume->instances()->count()) {
            Log::info('No instances using this volume. Nothing to do.');
            return true;
        }

        $updateSuccessful = $volume->instances()->each(function ($instance) use ($volume) {
            try {
                $response = $volume->availabilityZone->kingpinService()->put(
                    '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid . '/iops',
                    [
                        'json' => [
                            'limit' => $volume->iops,
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
                    'content' => $response->getBody()->getContents()
                ]);
                return false;
            }

            Log::debug('Volume ' . $volume->id . ' iops changed to ' . $volume->iops . ' on instance ' . $instance->id);

            return true;
        });

        if ($updateSuccessful === false) {
            $this->fail(new \Exception('Volume ' . $volume->id . ' failed to change iops to ' . $volume->iops));
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);

        return true;
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
