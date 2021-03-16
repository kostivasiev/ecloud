<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class CreateAutoDeployRule extends Job
{
    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $host = $this->model;
        $availabilityZone = $host->hostGroup->availabilityZone;

        // retrieved from the Conjurer UCS server profile (conjurer)
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/specification/' . $host->hostGroup->hostSpec->name . '/host/' . $host->id
        );
        $response = json_decode($response->getBody()->getContents());

        // $response->hardwareVersion
        // $response->hardwareVersion->interfaces , get the MAC address from eth0


        // POST

        $availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $host->hostGroup->vpc_id .'/hostgroup/' . $host->hostGroup->id .'/host',
            [
                'json' => [
                    'hostId' => $host->id,
                    'hardwareVersion' => $response->hardwareVersion,
                    'macAddress' => ,
                ],
            ]
        );


        /**
         * {
        "hostId": "string",
        "hardwareVersion": "string",
        "macAddress": "string"
        }
         */

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
