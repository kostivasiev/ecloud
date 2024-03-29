<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteServiceProfile extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $host = $this->model;
        $availabilityZone = $host->hostGroup->availabilityZone;

        // Check Exists
        try {
            $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() == 404) {
                Log::info("Service profile doesn't exist, skipping");
                return true;
            }
            throw $exception;
        }

        $availabilityZone->conjurerService()->delete(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id . '/host/' . $host->id
        );
    }
}
