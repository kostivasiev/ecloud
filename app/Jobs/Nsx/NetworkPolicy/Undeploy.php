<?php

namespace App\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private $model;

    public function __construct(NetworkPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $this->model->network->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/domains/default/security-policies/' . $this->model->id
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $message = $exception->hasResponse() ? json_decode($exception->getResponse()->getBody()->getContents()) : $exception->getMessage();
        $this->model->setSyncFailureReason($message);
    }
}
