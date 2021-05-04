<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateAutoDeployRule extends Job
{
    use Batchable, JobModel;

    private Host $model;

    public function __construct(Host $host)
    {
        $this->model = $host;
    }

    public function handle()
    {
        $availabilityZone = $this->model->hostGroup->availabilityZone;

        // Get the host spec from Conjurer
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->model->hostGroup->vpc->id .'/host/' . $this->model->id
        );
        $response = json_decode($response->getBody()->getContents());

        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $this->model->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::info(get_class($this) . 'Host MAC address: ' . $macAddress);

        // Add the host to the host group on VMWare
        $availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->hostGroup->vpc_id .'/hostgroup/' . $this->model->hostGroup->id .'/host',
            [
                'json' => [
                    'hostId' => $this->model->id,
                    'hardwareVersion' => $response->hardwareVersion,
                    'macAddress' => $macAddress,
                ],
            ]
        );
    }
}
