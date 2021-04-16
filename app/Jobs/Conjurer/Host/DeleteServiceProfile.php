<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteServiceProfile extends Job
{
    use Batchable;

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

        // Check Exists
        try {
            $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                $this->fail($exception);
            }
            Log::warning(get_class($this) . ' : Service Profile for Host ' . $host->id . ' not found, skipping.');
            return false;
        }

        try {
            $availabilityZone->conjurerService()->delete(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                $this->fail($exception);
                throw $exception;
            }
            Log::warning(get_class($this) . ' : Service Profile for Host ' . $host->id . ' was not found.');
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
