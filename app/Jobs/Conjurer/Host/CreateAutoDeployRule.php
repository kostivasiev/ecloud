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

        // Get the host spec from Conjurer
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id .'/host/' . $host->id
        );
        $response = json_decode($response->getBody()->getContents());

        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $host->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::info(get_class($this) . 'Host MAC address: ' . $macAddress);

        // Add the host to the host group on VMWare
        $availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $host->hostGroup->vpc_id .'/hostgroup/' . $host->hostGroup->id .'/host',
            [
                'json' => [
                    'hostId' => $host->id,
                    'hardwareVersion' => $response->hardwareVersion,
                    'macAddress' => $macAddress,
                ],
            ]
        );

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
