<?php

namespace App\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Nsx\NsxDeployCheck;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Log;

class DeployCheck extends NsxDeployCheck
{
    public function __construct(NetworkPolicy $model)
    {
        parent::__construct($model);

        $this->availabilityZone = $this->model->network->router->availabilityZone;
        $this->intentPath = '/infra/domains/default/security-policies/' . $this->model->id;
    }

    public function handle()
    {
        // NSX doesn't try to "realise" a NetworkPolicy until it has rules
        if (!count($this->model->networkRules)) {
            Log::info(get_class($this) . ' : No rules on the policy. Ignoring deploy check', ['id' => $this->model->id]);
            return;
        }

        parent::handle();
    }
}
