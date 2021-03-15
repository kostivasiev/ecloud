<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class CheckAvailableCompute extends Job
{
    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    /**
     * Check available stock of requested host speck on the UCS
     * @see https://185.197.63.78:8444/swagger/ui/index#/Compute_v2/Compute_v2_RetrieveAvailableNodes
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $host = $this->model;
        $availabilityZone = $host->hostGroup->availabilityZone;

        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/specification/' . $host->hostGroup->hostSpec->name . '/host/available'
        );

        $response = json_decode($response->getBody()->getContents());

        if (!is_array($response)) {
            $message = 'Failed to determine available stock for specification ' . $host->hostGroup->hostSpec->name;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        if (count($response) < 1) {
            $message = 'Insufficient stock for specification ' . $host->hostGroup->hostSpec->name;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $message = ($exception instanceof RequestException && $exception->hasResponse()) ?
            json_decode($exception->getResponse()->getBody()->getContents()) :
            $exception->getMessage();
        $this->model->setSyncFailureReason($message);    }
}
