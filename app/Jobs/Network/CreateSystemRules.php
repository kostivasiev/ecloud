<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Traits\V2\TaskJobs\AwaitResources;
use UKFast\Admin\Monitoring\AdminClient;

class CreateSystemRules extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $network = $this->task->resource;
        $router = $network->router;

        // identify LM collector for target AZ from monitoring API
        $client = app()->make(AdminClient::class)
            ->setResellerId($router->getResellerId());
        $collectors = $client->collectors()->getAll([
            'datacentre_id' => $router->availabilityZone->datacentre_site_id,
            'is_shared' => true,
        ]);

        if (count($collectors) < 0) {
            $this->info('No Collector found for datacentre', [
                'availability_zone_id' => $router->availabilityZone->id,
                'network_id' => $network->id,
                'router_id' => $router->id,
                'datacentre_site_id' => $router->availabilityZone->datacentre_site_id,
            ]);
            return;
        }

        // Create firewall rule in system policy allowing inbound traffic from the collector
        $firewallPolicy = $router->firewallPolicies()->where('name', '=', 'System')->first();

        if (!$firewallPolicy) {
            $this->info('System policy not found', [
                'network_id' => $network->id,
                'router_id' => $router->id,
            ]);
            return;
        }
        $policyRules = config('firewall.system.rules');

        $ipAddresses = [];
        foreach ($collectors as $collector) {
            $ipAddresses[] = $collector->ipAddress;
        }
        $ipAddresses = implode(',', $ipAddresses);

        // now we have the ip address
        foreach ($policyRules as $rule) {
            $firewallRule = app()->make(FirewallRule::class);
            $firewallRule->fill($rule);
            $firewallRule->source = $ipAddresses;
            $firewallRule->firewallPolicy()->associate($firewallPolicy);
            $firewallRule->save();

            foreach ($rule['ports'] as $port) {
                $firewallRulePort = app()->make(FirewallRulePort::class);
                $firewallRulePort->fill($port);
                $firewallRulePort->firewallRule()->associate($firewallRule);
                $firewallRulePort->save();
            }
            $firewallPolicy->syncSave();
        }
    }
}
