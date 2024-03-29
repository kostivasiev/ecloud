<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Router;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateManagementFirewallPolicies extends TaskJob
{
    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (!empty($this->task->data['management_router_id']) && !empty($this->task->data['management_network_id'])) {
            if (empty($this->task->data['firewall_policy_id'])) {
                $managementRouter = Router::find($this->task->data['management_router_id']);
                if (empty($managementRouter)) {
                    $message = 'Unable to load management router.';
                    $this->error($message);
                    $this->fail(new \Exception($message));
                    return;
                }

                if ($managementRouter->firewallPolicies()->count() > 0) {
                    $this->info('A management firewall policy was detected, skipping.', [
                        'vpc_id' => $managementRouter->vpc->id,
                        'availability_zone_id' => $managementRouter->availability_zone_id
                    ]);
                    return;
                }

                $this->info('Create Management Firewall Policy and Rules Start', [
                    'router_id' => $managementRouter->id,
                ]);

                $firewallPolicy = new FirewallPolicy([
                    'name' => 'Management_Firewall_Policy_for_' . $managementRouter->id,
                    'router_id' => $managementRouter->id,
                    'sequence' => 0,
                ]);
                $firewallPolicy->save();

                // Allow outbound 4222
                $firewallRule = new FirewallRule([
                    'name' => 'Allow_Ed_Proxy_outbound_' . $managementRouter->id,
                    'sequence' => 10,
                    'firewall_policy_id' => $firewallPolicy->id,
                    'source' => 'ANY',
                    'destination' => 'ANY',
                    'action' => 'ALLOW',
                    'direction' => 'OUT',
                    'enabled' => true
                ]);
                $firewallRule->save();
                $firewallRulePort = new FirewallRulePort([
                    'firewall_rule_id' => $firewallRule->id,
                    'protocol' => 'TCP',
                    'source' => 'ANY',
                    'destination' => '4222'
                ]);
                $firewallRulePort->save();
                $firewallRulePort = new FirewallRulePort([
                    'firewall_rule_id' => $firewallRule->id,
                    'protocol' => 'TCP',
                    'source' => 'ANY',
                    'destination' => '3128'
                ]);
                $firewallRulePort->save();

                $firewallPolicy->syncSave();

                $this->task->updateData('firewall_policy_id', $firewallPolicy->id);

                $this->info('Create Firewall Policy and Rules End', [
                    'router_id' => $managementRouter->id,
                ]);
            } else {
                $firewallPolicy = FirewallPolicy::findOrFail($this->task->data['firewall_policy_id']);
            }

            if ($firewallPolicy) {
                $this->awaitSyncableResources([
                    $firewallPolicy->id,
                ]);
            }
        }
    }
}
