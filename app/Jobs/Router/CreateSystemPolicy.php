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

        $systemPolicy = $router->whereHas('firewallPolicies', function ($query) {
            $query->where('name', '=', 'System');
        })->first();

        if ($systemPolicy) {
            $this->info('A system policy was already detected, skipping.', [
                'vpc_id' => $router->vpc->id,
                'availability_zone_id' => $router->availability_zone_id
            ]);
            return;
        }

        $this->info('Create System Policy and Rules Start');

        $policyConfig = config('firewall.system');

        $firewallPolicy = app()->make(FirewallPolicy::class);
        $firewallPolicy->fill($policyConfig);
        $firewallPolicy->router_id = $router->id;
        $firewallPolicy->syncSave();

        $this->task->updateData('system_firewall_policy_id', $firewallPolicy->id);

        $this->info('Create System Policy and Rules End');

        $this->awaitSyncableResources([
            $firewallPolicy->id,
        ]);
    }
}
