<?php

namespace App\Jobs\Kingpin\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class CreateCluster extends Job
{
    private $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $hostGroup = $this->model;

        // Check if it already exists and if do skip creating it
        $response = $hostGroup->availabilityZone->kingpinService()
            ->get('/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id);
        if ($response && $response->getStatusCode() === 200) {
            Log::info(get_class($this) . ' : Skipped', [
                'id' => $hostGroup->id,
                'status_code' => $response->getStatusCode(),
            ]);
            return true;
        }

        try {
            $response = $hostGroup->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup',
                [
                    'json' => [
                        'hostGroupId' => $hostGroup->id,
                        'shared' => false,
                    ],
                ]
            );
        } catch (ServerException $exception) {
            $response = $exception->getResponse();
        }

        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $hostGroup->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents(),
            ]);
            $this->fail(new \Exception('Failed to create ' . $hostGroup->id));
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
