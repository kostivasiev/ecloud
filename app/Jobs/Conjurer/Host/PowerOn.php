<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PowerOn extends Job
{
    use Batchable, LoggableModelJob;

    private Host $model;

    public function __construct(Host $host)
    {
        $this->model = $host;
    }

    public function handle()
    {
        $availabilityZone = $this->model->hostGroup->availabilityZone;

        // Check whether host exists and is powered on
        try {
            $response = $availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->model->hostGroup->vpc_id .'/hostgroup/' . $this->model->hostGroup->id . '/host/' . $this->model->mac_address
            );

            $response = json_decode($response->getBody()->getContents());

            if ($response->powerState === 'poweredOn') {
                Log::debug('Host already powered on, skipping');
                return true;
            }
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() != 404) {
                throw $exception;
            }
        }

        $availabilityZone->conjurerService()->post(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->model->hostGroup->vpc->id .'/host/' . $this->model->id . '/power'
        );
    }
}
