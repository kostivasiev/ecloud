<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private $model;

    public function __construct(FirewallPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        // TODO :- Move this to the \App\Jobs\Sync\FirewallPolicy\Delete as chained jobs BEFORE deleting the policy!
        // See https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/590#note_712450
        $this->model->firewallRules->each(function ($firewallRule) {
            $firewallRule->firewallRulePorts->each(function ($firewallRulePort) {
                $firewallRulePort->delete();
            });
            $firewallRule->delete();
        });

        $this->model->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/domains/default/gateway-policies/' . $this->model->id
        );

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
