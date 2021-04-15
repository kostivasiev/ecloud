<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PowerOff extends Job
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
        if (!$this->batch()->cancelled()) {
            $host = $this->model;
            $hostGroup = $host->hostGroup;
            $availabilityZone = $host->hostGroup->availabilityZone;

            try {
                $availabilityZone->conjurerService()->delete(
                    '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id . '/host/' . $host->id . '/power'
                );
            } catch (RequestException $exception) {
                if ($exception->getCode() != 404) {
                    $this->fail($exception);
                    throw $exception;
                }
                Log::warning(get_class($this) . ' : Host ' . $host->id . ' was not powered off.');
                return;
            }
        }
        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
