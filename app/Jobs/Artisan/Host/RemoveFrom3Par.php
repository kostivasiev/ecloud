<?php

namespace App\Jobs\Artisan\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RemoveFrom3Par extends Job
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
            $availabilityZone->artisanService()->get(
                '/api/v2/san/' . $availabilityZone->san_name . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() == 404) {
                Log::info("Host doesn't exist on 3PAR, skipping");
                return true;
            }
            throw $exception;
        }

        $availabilityZone->artisanService()->delete(
            '/api/v2/san/' . $availabilityZone->san_name . '/host/' . $host->id
        );
    }
}
