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
            if ($exception->getCode() != 404) {
                $this->fail($exception);
            }
            Log::warning(get_class($this) . ' : 3Par for Host ' . $host->id . ' could not be retrieved, skipping.');
            return false;
        }

        try {
            $availabilityZone->artisanService()->delete(
                '/api/v2/san/' . $availabilityZone->san_name . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                $this->fail($exception);
                throw $exception;
            }
            Log::warning(get_class($this) . ' : Host ' . $host->id . ' was not removed from 3Par.');
            return;
        }
    }
}
