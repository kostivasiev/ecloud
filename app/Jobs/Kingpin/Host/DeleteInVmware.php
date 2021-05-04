<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteInVmware extends Job
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
        try {
            $response = $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id . '/host/' . $this->host->id
            );
            $response = json_decode($response->getBody()->getContents());
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                $this->fail($exception);
            }
            Log::warning(get_class($this) . ' : Host was not found on UCS, skipping.');
            return false;
        }

        $macAddress = collect($response->interfaces)->firstWhere('name', '=', 'eth0')->address;
        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $this->host->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::debug('MAC address: ' . $macAddress);

        try {
            $availabilityZone->kingpinService()->delete(
                '/api/v2/vpc/' . $hostGroup->vpc_id . '/hostgroup/' . $hostGroup->id . '/host/' . $macAddress
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            Log::warning(get_class($this) . ' : Host could not be deleted, skipping.');
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->host->id]);
    }
}
