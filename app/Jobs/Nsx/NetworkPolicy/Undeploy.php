<?php

namespace App\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class Undeploy extends Job
{
    use Batchable, LoggableModelJob;

    private NetworkPolicy $model;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->model = $networkPolicy;
    }

    public function handle()
    {
        $this->model->network->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/domains/default/security-policies/' . $this->model->id
        );
    }
}
