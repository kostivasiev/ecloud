<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Deploy as DeploySecurityGroup;
use App\Jobs\Nsx\NetworkPolicy\Deploy as DeployNetworkPolicy;
use App\Jobs\Nsx\DeployCheck;
use App\Jobs\Sync\Completed;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(NetworkPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $jobs = [
            new DeploySecurityGroup($this->model),
            new DeployCheck(
                $this->model,
                $this->model->network->router->availabilityZone,
                '/infra/domains/default/groups/'
            ),
            new DeployNetworkPolicy($this->model),
        ];

        if (count($this->model->networkRules) > 0) {
            // NSX doesn't try to "realise" a NetworkPolicy until it has rules
            $jobs[] = new DeployCheck(
                $this->model,
                $this->model->network->router->availabilityZone,
                '/infra/domains/default/security-policies/'
            );
        }

        $jobs[] = new Completed($this->model);

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
