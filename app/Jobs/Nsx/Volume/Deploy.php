<?php

namespace App\Jobs\Nsx\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $model;
    protected $originalCapacity;
    protected $originalIops;

    public function __construct(Volume $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $volume = $this->model;
        $this->originalCapacity = $volume->getOriginal('capacity');
        $this->originalIops = $volume->getOriginal('iops');

        // If vmware_uuid is null, then this is a create job
        if (is_null($volume->vmware_uuid)) {
            $response = $this->create();
            if ($response->getStatusCode() === 200) {
                $this->iopsChange();
            }
        }

        if ($this->model->capacity !== $this->originalCapacity) {
            $response = $this->capacityChange();
        }

        if ($this->model->iops !== $this->originalIops) {
            $response = $this->iopsChange();
        }

        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $this->model->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            $this->fail(new \Exception('Failed to create "' . $this->model->id . '"'));
            return false;
        }

        // If the vmware_uuid has been populated, then save the model.
        if (($response->getStatusCode() === 200) &&
            ($this->model->getOriginal('vmware_uuid') !== $this->model->vmware_uuid)) {
            $this->model->save();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function create()
    {
        try {
            $response = $this->model->availabilityZone->kingpinService()->post(
                '/api/v1/vpc/' . $this->model->vpc_id . '/volume',
                [
                    'json' => [
                        'volumeId' => $this->model->id,
                        'sizeGiB' => $this->model->capacity,
                        'shared' => false,
                    ]
                ]
            );
        } catch (ServerException $exception) {
            return $exception->getResponse();
        }

        $responseContents = json_decode($response->getBody()->getContents());
        $this->model->vmware_uuid = $responseContents->uuid;
        return $response;
    }

    public function capacityChange()
    {
        $endpoint = '/api/v1/vpc/' . $this->model->vpc_id . '/volume/' . $this->model->vmware_uuid . '/size';

        if ($this->model->instances()->count() > 0) {
            $instance = $this->model->instances()->first();
            $endpoint = '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $this->model->vmware_uuid . '/size';
        }

        $response = $this->model->availabilityZone->kingpinService()->put(
            $endpoint,
            [
                'json' => [
                    'sizeGiB' => $this->model->capacity
                ]
            ]
        );

        Log::info('Volume ' . $this->model->getKey() . ' capacity increased from ' . $this->originalCapacity . ' to ' . $this->model->capacity);

        return $response;
    }

    public function iopsChange()
    {
        if ($this->model->instances()->count() > 0) {
            $instance = $this->model->instances()->first();
            $endpoint = '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/volume/' . $this->model->vmware_uuid . '/iops';

            $response = $this->model->availabilityZone->kingpinService()->put(
                $endpoint,
                [
                    'json' => [
                        'limit' => $this->model->iops
                    ]
                ]
            );

            Log::info('Volume ' . $this->model->getKey() . ' iops changed from ' . $this->originalIops . ' to ' . $this->model->iops);

            return $response;
        }
    }
}
