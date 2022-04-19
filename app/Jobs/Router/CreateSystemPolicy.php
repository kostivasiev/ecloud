<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Models\V2\FirewallPolicy;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateSystemPolicy extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $router = $this->task->resource;

        if ($router->isManaged()) {
            $this->info('Router is management resource, skipping');
            return;
        }

        if (empty($this->task->data['system_firewall_policy_id'])) {
            $systemPolicy = $router->firewallPolicies()->where('name', '=', 'System')->first();
            if ($systemPolicy) {
                $this->info('A system policy was already detected, skipping.', [
                    'firewall_policy_id' => $systemPolicy->id,
                    'vpc_id' => $router->vpc->id,
                    'availability_zone_id' => $router->availability_zone_id
                ]);
                return;
            }

            $policyConfig = config('firewall.system');
            $firewallPolicy = app()->make(FirewallPolicy::class);
            $firewallPolicy->fill($policyConfig);
            $firewallPolicy->router_id = $router->id;
            $firewallPolicy->save();

            $firewallPolicy->createRulesAndPorts($policyConfig['rules']);

            $firewallPolicy->syncSave();

            $this->task->updateData('system_firewall_policy_id', $firewallPolicy->id);
        } else {
            $firewallPolicy = FirewallPolicy::find($this->task->data['system_firewall_policy_id']);
        }

        $this->awaitSyncableResources([
            $firewallPolicy->id,
        ]);
    }
}
