<?php

namespace App\Jobs\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable;

    private $firewallPolicy;

    public function __construct(FirewallPolicy $firewallPolicy)
    {
        $this->firewallPolicy = $firewallPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->firewallPolicy->id]);

        // TODO :- Move this to the \App\Jobs\Sync\FirewallPolicy\Delete as chained jobs BEFORE deleting the policy!
        // See https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/590#note_712450
        $this->firewallPolicy->firewallRules->each(function ($firewallRule) {
            $firewallRule->firewallRulePorts->each(function ($firewallRulePort) {
                $firewallRulePort->delete();
            });
            $firewallRule->delete();
        });

        $this->firewallPolicy->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/domains/default/gateway-policies/' . $this->firewallPolicy->id
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->firewallPolicy->id]);
    }
}
