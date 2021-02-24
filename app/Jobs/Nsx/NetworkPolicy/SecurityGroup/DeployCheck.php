<?php

namespace App\Jobs\Nsx\NetworkPolicy\SecurityGroup;

use App\Jobs\Nsx\NsxDeployCheck;
use App\Models\V2\NetworkPolicy;

class DeployCheck extends NsxDeployCheck
{
    public function __construct(NetworkPolicy $model)
    {
        parent::__construct($model);

        $this->availabilityZone = $this->model->network->router->availabilityZone;
        $this->intentPath = '/infra/domains/default/groups/' . $this->model->id;
    }
}
