<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateAutoDeployRule extends Job
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

        // Check whether host exists
        try {
            $availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->model->hostGroup->vpc_id .'/hostgroup/' . $this->model->hostGroup->id . '/host/' . $this->model->mac_address
            );

            Log::debug('Host already exists, skipping');
            return true;
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() != 404) {
                throw $exception;
            }
        }

        // Get the host spec from Conjurer
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->model->hostGroup->vpc->id .'/host/' . $this->model->id
        );
        $response = json_decode($response->getBody()->getContents());

        // Add the host to the host group on VMWare
        $availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->hostGroup->vpc_id .'/hostgroup/' . $this->model->hostGroup->id . '/host',
            [
                'json' => [
                    'hostId' => $this->model->id,
                    'hardwareVersion' => $response->hardwareVersion,
                    'macAddress' => $this->model->mac_address,
                ],
            ]
        );
    }
}
