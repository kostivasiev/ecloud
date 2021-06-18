<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CheckProfileApplied extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 60;
    public $backoff = 60;

    private Host $model;

    public function __construct(Host $host)
    {
        $this->model = $host;
    }

    public function handle()
    {
        $availabilityZone = $this->model->hostGroup->availabilityZone;
        $hostGroup = $this->model->hostGroup;

        try {
            $response = $availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $hostGroup->vpc_id . '/hostgroup/' . $hostGroup->id . '/host/' . $this->model->mac_address
            );
            $response = json_decode($response->getBody()->getContents());
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() == 404) {
                Log::warning(
                    'Host ' . $this->model->id . ' not found, retrying in ' . $this->backoff . ' seconds'
                );
                return $this->release($this->backoff);
            }
            throw $exception;
        }
        if (!$response->networkProfileApplied) {
            Log::info('Host ' . $this->model->id . ' found. Waiting for network profile to be applied...');
            return $this->release($this->backoff);
        }
    }
}
