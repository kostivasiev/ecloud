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

        // TODO :- Move this to the \App\Jobs\Sync\NetworkPolicy\Delete as chained jobs BEFORE deleting the policy!
        // See https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/590#note_712450
        $this->model->networkRules->each(function ($networkRule) {
            $networkRule->networkRulePorts->each(function ($networkRulePort) {
                $networkRulePort->delete();
            });
            $networkRule->delete();
        });

        $this->model->network->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/domains/default/security-policies/' . $this->model->id
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
