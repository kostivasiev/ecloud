<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Traits\V2\JobModel;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class Attach extends Job
{
    use JobModel;

    private Volume $model;
    private Instance $instance;

    public function __construct(Volume $volume, Instance $instance)
    {
        $this->model = $volume;
        $this->instance = $instance;
    }

    public function handle()
    {
        if ($this->instance->volumes()->get()->count() > config('volume.instance.limit', 15)) {
            $this->fail(new \Exception(
                'Volume ' . $this->model->id . ' failed to attach to instance ' .
                $this->instance->id . ', volume limit exceeded'
            ));
            return false;
        }

        try {
            $response = $this->instance->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id
            );
        } catch (ServerException $exception) {
            $response = $exception->getResponse();
        }

        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $this->model->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            $this->fail(new \Exception('Volume ' . $this->model->id . ' failed attachment'));
            return false;
        }

        $json = json_decode($response->getBody()->getContents());
        if (!$json) {
            $this->fail(new \Exception('Volume ' . $this->model->id . ' failed attachment, invalid JSON'));
            return false;
        }

        foreach ($json->volumes as $volume) {
            if ($this->model->vmware_uuid == $volume->uuid) {
                Log::info('Volume is already attached to instance, nothing to do');
                return true;
            }
        }

        try {
            $response = $this->instance->availabilityZone->kingpinService()
                ->post(
                    '/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->id . '/volume/attach',
                    [
                        'json' => [
                            'volumeUUID' => $this->model->vmware_uuid
                        ]
                    ]
                );
        } catch (ServerException $exception) {
            $response = $exception->getResponse();
        }

        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $this->model->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            $this->fail(new \Exception('Volume ' . $this->model->id . ' failed attachment'));
            return false;
        }

        Log::debug('Volume ' . $this->model->id . ' has been attached to instance ' . $this->instance->id);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());

        // Detach
        $this->instance->volumes()->detach($this->model);
    }
}
