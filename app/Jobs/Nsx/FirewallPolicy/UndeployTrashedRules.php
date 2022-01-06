<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\TaskJob;
use App\Services\V2\NsxService;

class UndeployTrashedRules extends TaskJob
{
    public function handle()
    {
        $firewallPolicy = $this->task->resource;
        $router = $firewallPolicy->router;
        $availabilityZone = $router->availabilityZone;

        $rulesResponse = $availabilityZone->nsxService()->get(
            sprintf(NsxService::GET_GATEWAY_POLICY_RULES, $firewallPolicy->id)
        );
        $rulesResponseBody = json_decode($rulesResponse->getBody()->getContents());
        $rulesToRemove = [];

        foreach ($rulesResponseBody->results as $result) {
            $trashedRule = $firewallPolicy->firewallRules()->withTrashed()->find($result->id);
            if ($trashedRule != null && $trashedRule->trashed()) {
                $rulesToRemove[] = $result->id;
            }
        }

        if (count($rulesToRemove) < 1) {
            $this->debug('No rules to remove, skipping');
        }

        foreach ($rulesToRemove as $ruleToRemove) {
            $this->debug("Removing firewall rule", ['ruleToRemove' => $ruleToRemove]);

            $availabilityZone->nsxService()->delete(
                sprintf(NsxService::DELETE_GATEWAY_POLICY_RULE, $firewallPolicy->id, $ruleToRemove)
            );
        }
    }
}
