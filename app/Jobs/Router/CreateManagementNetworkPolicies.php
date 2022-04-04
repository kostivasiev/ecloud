<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Models\V2\Router;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateManagementNetworkPolicies extends TaskJob
{
    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if (!empty($this->task->data['management_router_id']) && !empty($this->task->data['management_network_id'])) {
            $managementRouter = Router::find($this->task->data['management_router_id']);
            if (empty($managementRouter)) {
                $message = 'Unable to load management router.';
                $this->error($message);
                $this->fail(new \Exception($message));
                return;
            }

            if ($managementRouter->vpc->advanced_networking === false) {
                $this->info('Advanced networking is not enabled on the vpc, skipping');
                return;
            }

            if (empty($this->task->data['network_policy_id'])) {
                $managementNetwork = Network::find($this->task->data['management_network_id']);
                if (empty($managementNetwork)) {
                    $message = 'Unable to load management network.';
                    $this->error($message);
                    $this->fail(new \Exception($message));
                    return;
                }

                if ($managementNetwork->networkPolicy()->exists()) {
                    $this->info('A management network policy was detected, skipping.', [
                        'vpc_id' => $managementRouter->vpc->id,
                        'availability_zone_id' => $managementRouter->availability_zone_id
                    ]);
                    return;
                }

                $this->info('Create Management Network Policy and Rules Start', [
                    'network_id' => $managementNetwork->id,
                ]);

                $networkPolicy = new NetworkPolicy([
                    'name' => 'Management_Network_Policy_for_' . $managementNetwork->id,
                    'network_id' => $managementNetwork->id,
                ]);
                $networkPolicy->save();

                // Allow inbound 4222
                $networkRule = new NetworkRule([
                    'name' => 'Allow_Ed_Proxy_outbound_' . $managementNetwork->id,
                    'sequence' => 10,
                    'network_policy_id' => $networkPolicy->id,
                    'source' => 'ANY',
                    'destination' => 'ANY',
                    'action' => 'ALLOW',
                    'direction' => 'OUT',
                    'enabled' => true
                ]);
                $networkRule->save();
                $networkRulePort = new NetworkRulePort([
                    'network_rule_id' => $networkRule->id,
                    'protocol' => 'TCP',
                    'source' => 'ANY',
                    'destination' => '4222'
                ]);
                $networkRulePort->save();
                $networkRulePort = new NetworkRulePort([
                    'network_rule_id' => $networkRule->id,
                    'protocol' => 'TCP',
                    'source' => 'ANY',
                    'destination' => '3128'
                ]);
                $networkRulePort->save();

                $networkPolicy->syncSave();
                $this->task->updateData('network_policy_id', $networkPolicy->id);

                $this->info('Create Management Network Policy and Rules End', [
                    'network_id' => $managementNetwork->id,
                ]);
            } else {
                $networkPolicy = NetworkPolicy::findOrFail($this->task->data['network_policy_id']);
            }

            if ($networkPolicy) {
                $this->awaitSyncableResources([
                    $networkPolicy->id,
                ]);
            }
        }
    }
}
