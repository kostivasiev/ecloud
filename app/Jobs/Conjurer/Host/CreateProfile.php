<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class CreateProfile extends Job
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

        $response = $availabilityZone->conjurerService()->post(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id .'/host',
            [
                'json' => [
                    'specificationName' => $host->hostGroup->hostSpec->name,
                    'hostId' => $host->id,
                ],
            ]
        );

        $response = json_decode($response->getBody()->getContents());

        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

        if (!empty($macAddress)) {
            Log::debug('Host was created on UCS, MAC address: ' . $macAddress);
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
