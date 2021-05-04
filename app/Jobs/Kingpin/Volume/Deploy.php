<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
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

        if (!empty($volume->vmware_uuid)) {
            Log::info('Volume already deployed. Nothing to do.');
            return true;
        }

        try {
            $response = $volume->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $volume->vpc_id . '/volume',
                [
                    'json' => [
                        'volumeId' => $volume->id,
                        'sizeGiB' => $volume->capacity,
                        'shared' => false,
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
            $this->fail(new \Exception('Failed to create ' . $volume->id));
            return false;
        }

        $json = json_decode($response->getBody()->getContents());
        if (!isset($json->uuid)) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $volume->id,
                'json' => $json,
            ]);
            throw new \Exception('Kingpin call failed to return UUID for volume');
        }
        $volume->vmware_uuid = $json->uuid;

        Log::debug(get_class($this) . ' : Deployed volume', [
            'id' => $volume->id,
            'uuid' => $volume->vmware_uuid,
        ]);

        $volume->saveQuietly();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
