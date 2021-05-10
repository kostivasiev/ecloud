<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateProfile extends Job
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
        $hostGroup = $this->model->hostGroup;

        $response = $availabilityZone->conjurerService()->post(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id .'/host',
            [
                'json' => [
                    'specificationName' => $hostGroup->hostSpec->name,
                    'hostId' => $this->model->id,
                ],
            ]
        );

        $response = json_decode($response->getBody()->getContents());
        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;
        if (!empty($macAddress)) {
            Log::debug('Host was created on UCS, MAC address: ' . $macAddress);
        }
    }
}
