<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateAutoDeployRule extends Job
{
    use Batchable;

    private Host $host;

    public function __construct(Host $host)
    {
        $this->host = $host;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->host->id]);

        $availabilityZone = $this->host->hostGroup->availabilityZone;

        // Get the host spec from Conjurer
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->host->hostGroup->vpc->id .'/host/' . $this->host->id
        );
        $response = json_decode($response->getBody()->getContents());

        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $this->host->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::info(get_class($this) . 'Host MAC address: ' . $macAddress);

        // Add the host to the host group on VMWare
        $availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->host->hostGroup->vpc_id .'/hostgroup/' . $this->host->hostGroup->id .'/host',
            [
                'json' => [
                    'hostId' => $this->host->id,
                    'hardwareVersion' => $response->hardwareVersion,
                    'macAddress' => $macAddress,
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->host->id]);
    }
}
