<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\TaskJob;

class Undeploy extends TaskJob
{
    public function handle()
    {
        $firewallPolicy = $this->task->resource;

        $firewallPolicy->firewallRules->each(function ($firewallRule) {
            $firewallRule->firewallRulePorts->each(function ($firewallRulePort) {
                $firewallRulePort->delete();
            });
            $firewallRule->delete();
        });

        $firewallPolicy->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/domains/default/gateway-policies/' . $firewallPolicy->id
        );
    }
}
