<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteInVmware extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 3;
    public $backoff = 5;

    private Host $model;

    public function __construct(Host $host)
    {
        $this->model = $host;
    }

    public function handle()
    {
        $host = $this->model;
        $hostGroup = $host->hostGroup;
        $availabilityZone = $hostGroup->availabilityZone;

        if (empty($host->mac_address)) {
            Log::warning("MAC address empty, skipping");
            return true;
        }

        // Check Exists
        try {
            $availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id . '/host/' . $host->mac_address
            );
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() == 404) {
                Log::info("Host doesn't exist, skipping");
                return true;
            }
            throw $exception;
        }

        $availabilityZone->kingpinService()->delete(
            '/api/v2/vpc/' . $hostGroup->vpc_id . '/hostgroup/' . $hostGroup->id . '/host/' . $host->mac_address
        );
    }
}
