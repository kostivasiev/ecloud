<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use Illuminate\Support\Facades\Log;

class CreateLanPolicy extends Job
{
    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $host = $this->model;
        $vpc = $host->hostGroup->vpc;
        $availabilityZone = $host->hostGroup->availabilityZone;

        $queryLanPolicyResponse = $availabilityZone->conjurerService()->get('/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $vpc->id);

        exit(print_r($queryLanPolicyResponse));

        if (!$queryLanPolicyResponse || $queryLanPolicyResponse->getStatusCode() != 200) {
            throw new \Exception('Failed checking VPC ' . $vpc->id . ' LAN policy exists on UCS');
        }



        if (1) {
            Log::debug('VPC LAN Policy already exists. Nothing to do.');
            return true;
        }


        $createLanPolicyResponse = $availabilityZone->conjurerService()->get('/api/v2/compute/{computeName}/vpc'); //TODO







        // TODO Check if the VPC has a LAN policy and create one if it does not.

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $message = $exception->hasResponse() ? json_decode($exception->getResponse()->getBody()->getContents()) : $exception->getMessage();
        $this->model->setSyncFailureReason($message);
    }
}
