<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\TaskJob;
use App\Services\V2\NsxService;

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
            sprintf(NsxService::DELETE_GATEWAY_POLICY, $firewallPolicy->id)
        );
    }
}
