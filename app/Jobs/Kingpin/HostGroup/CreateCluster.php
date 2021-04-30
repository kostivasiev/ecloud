<?php

namespace App\Jobs\Kingpin\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateCluster extends Job
{
    use Batchable;

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
        try {
            $response = $hostGroup->availabilityZone->kingpinService()
                ->get('/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id);
            if ($response->getStatusCode() == 200) {
                Log::debug(get_class($this) . ' : HostGroup already exists, nothing to do.', ['id' => $this->model->id]);
                return true;
            }
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
        }

        $hostGroup->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup',
            [
                'json' => [
                    'hostGroupId' => $hostGroup->id,
                    'shared' => false,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $message = $exception->getMessage();
        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $json = json_decode($exception->getResponse()->getBody()->getContents());
            Log::error('Request Exception', [
                'response_json' => $json,
                'exception' => $exception,
            ]);
        }
        $this->model->setSyncFailureReason($message);
    }
}
