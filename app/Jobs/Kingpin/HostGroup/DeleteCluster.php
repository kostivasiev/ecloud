<?php

namespace App\Jobs\Kingpin\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class DeleteCluster extends Job
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

        // Check if it already exists and if doesn't skip deleting it
        try {
            $response = $hostGroup->availabilityZone->kingpinService()
                ->get('/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            if ($exception->getCode() != 404) {
                throw $exception;
            }
        }

        if ($response->getStatusCode() === 200) {
            $hostGroup->availabilityZone->kingpinService()->delete(
                '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id
            );
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $message = ($exception instanceof RequestException && $exception->hasResponse()) ?
            $exception->getResponse()->getBody()->getContents() :
            $exception->getMessage();
        $this->model->setSyncFailureReason($message);
    }
}
