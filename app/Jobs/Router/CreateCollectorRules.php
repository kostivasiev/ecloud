<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Traits\V2\TaskJobs\AwaitResources;
use UKFast\Admin\Monitoring\AdminClient;

class CreateCollectorRules extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $router = $this->task->resource;

        if ($router->isManaged()) {
            $this->info('Router is management resource, skipping');
            return;
        }

        if (empty($this->task->data['collector_firewall_policy_id'])) {
            // identify LM collector for target AZ from monitoring API
            $client = app()->make(AdminClient::class)
                ->setResellerId($router->getResellerId());
            $collectors = $client->collectors()->getAll([
                'datacentre_id' => $router->availabilityZone->datacentre_site_id,
                'is_shared' => true,
            ]);

            if (empty($collectors)) {
                $this->info('No Collector found for datacentre', [
                    'availability_zone_id' => $router->availabilityZone->id,
                    'datacentre_site_id' => $router->availabilityZone->datacentre_site_id,
                ]);
                return;
            }

            if (empty($this->task->data['system_firewall_policy_id'])) {
                $this->fail(new \Exception('Failed to get System Firewall Policy'));
                return false;
            }

            $firewallPolicy = FirewallPolicy::find($this->task->data['system_firewall_policy_id']);
            $ipAddresses = [];
            foreach ($collectors as $collector) {
                $ipAddresses[] = $collector->ipAddress;
            }
            $ipAddresses = implode(',', $ipAddresses);

            // now we have the ip address
            foreach (config('firewall.rule_templates') as $rule) {
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
            }
            $firewallPolicy->syncSave();
            $this->task->updateData('collector_firewall_policy_id', $firewallPolicy->id);
        } else {
            $firewallPolicy = FirewallPolicy::find($this->task->data['collector_firewall_policy_id']);
        }

        $this->awaitSyncableResources([
            $firewallPolicy->id,
        ]);
    }
}
