<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CheckOnline extends Job
{
    use Batchable;

    public $tries = 60;
    public $backoff = 60;

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

        // Get the host spec from Conjurer
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id .'/host/' . $this->host->id
        );
        $response = json_decode($response->getBody()->getContents());

        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;

        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $this->host->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::debug('MAC address: ' . $macAddress);

        try {
            $response = $availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $hostGroup->vpc_id . '/hostgroup/' . $hostGroup->id . '/host/' . $macAddress
            );
            $response = json_decode($response->getBody()->getContents());
        } catch (RequestException $exception) {
            if ($exception->getCode() == 404) {
                throw new \Exception('Host ' . $this->host->id . ' was not found. Waiting for Host to come online...');
            }
        }

        if ($response->powerState !== 'poweredOn') {
            throw new \Exception('Host ' . $this->host->id . ' was found. Waiting for Host to power on...');
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->host->id]);
    }
}
