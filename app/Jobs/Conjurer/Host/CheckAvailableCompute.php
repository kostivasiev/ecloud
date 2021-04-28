<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CheckAvailableCompute extends Job
{
    use Batchable;

    private Host $host;

    public function __construct(Host $host)
    {
        $this->host = $host;
    }

    /**
     * Check available stock of requested host speck on the UCS
     * @see https://185.197.63.78:8444/swagger/ui/index#/Compute_v2/Compute_v2_RetrieveAvailableNodes
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->host->id]);

        $availabilityZone = $this->host->hostGroup->availabilityZone;

        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/specification/' . $this->host->hostGroup->hostSpec->name . '/host/available'
        );

        $response = json_decode($response->getBody()->getContents());

        if (!is_array($response)) {
            $message = 'Failed to determine available stock for specification ' . $this->host->hostGroup->hostSpec->name;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        if (count($response) < 1) {
            $message = 'Insufficient stock for specification ' . $this->host->hostGroup->hostSpec->name;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->host->id]);
    }
}
