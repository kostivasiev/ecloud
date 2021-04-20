<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateProfile extends Job
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
        $hostGroup = $this->host->hostGroup;

        $response = $availabilityZone->conjurerService()->post(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id .'/host',
            [
                'json' => [
                    'specificationName' => $hostGroup->hostSpec->name,
                    'hostId' => $this->host->id,
                ],
            ]
        );

        $response = json_decode($response->getBody()->getContents());

        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

        if (!empty($macAddress)) {
            Log::debug('Host was created on UCS, MAC address: ' . $macAddress);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->host->id]);
    }
}
