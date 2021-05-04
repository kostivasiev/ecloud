<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CheckAvailableCompute extends Job
{
    use Batchable, JobModel;

    private Host $model;

    public function __construct(Host $host)
    {
        $this->model = $host;
    }

    /**
     * Check available stock of requested host speck on the UCS
     * @see https://185.197.63.78:8444/swagger/ui/index#/Compute_v2/Compute_v2_RetrieveAvailableNodes
     */
    public function handle()
    {
        $availabilityZone = $this->model->hostGroup->availabilityZone;
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/specification/' . $this->model->hostGroup->hostSpec->name . '/host/available'
        );
        $response = json_decode($response->getBody()->getContents());

        if (!is_array($response)) {
            $message = 'Failed to determine available stock for specification ' . $this->model->hostGroup->hostSpec->name;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        if (count($response) < 1) {
            $message = 'Insufficient stock for specification ' . $this->model->hostGroup->hostSpec->name;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }
    }
}
