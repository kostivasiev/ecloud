<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class CheckOnline extends Job
{
    public $tries = 500;

    public $backoff = 20;

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

        Log::debug('MAC address: ' . $macAddress);

        try {
            $response = $host->hostGroup->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $host->hostGroup->vpc_id . '/hostgroup/' . $host->hostGroup->id . '/host/' . $macAddress
            );
            $response = json_decode($response->getBody()->getContents());
        } catch (RequestException $exception) {
            if ($exception->getCode() == 404) {
                Log::debug('Waiting for Host ' . $host->id . ' to come online...');
                $this->release();
                return false;
            }
        }

        if ($response->powerState !== 'poweredOn') {
            Log::debug('Waiting for Host ' . $host->id . ' power state...');
            $this->release();
            return false;
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
