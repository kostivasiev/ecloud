<?php
namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Traits\V2\TaskJobs\AwaitResources;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CreateManagementNetworkPolicies extends TaskJob
{
    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $router = $this->task->resource;
        if (!empty($this->task->data['management_router_id']) && !empty($this->task->data['management_network_id'])) {
            $networkPolicy = false;
            if ($router->vpc->advanced_networking) {
                if (empty($this->task->data['network_policy_id'])) {
                    $managementNetwork = Network::find($this->task->data['management_network_id']);
                    if ($managementNetwork) {
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
                            'name' => 'Allow_Ed_on_Port_4222_inbound_' . $managementNetwork->id,
                            'sequence' => 10,
                            'network_policy_id' => $networkPolicy->id,
                            'source' => 'ANY',
                            'destination' => 'ANY',
                            'action' => 'ALLOW',
                            'direction' => 'IN',
                            'enabled' => true
                        ]);
                        $networkRule->save();
                        $networkRulePort = new NetworkRulePort([
                            'network_rule_id' => $networkRule->id,
                            'protocol' => 'TCP',
                            'source' => '4222',
                            'destination' => '4222'
                        ]);
                        $networkRulePort->save();

                        // Block all outbound
                        (new NetworkRule([
                            'name' => 'Block_all_outbound_' . $managementNetwork->id,
                            'sequence' => 10,
                            'network_policy_id' => $networkPolicy->id,
                            'source' => 'ANY',
                            'destination' => 'ANY',
                            'action' => 'REJECT',
                            'direction' => 'OUT',
                            'enabled' => true
                        ]))->save();
                        $networkPolicy->syncSave();

                        $this->task->updateData('network_policy_id', $networkPolicy->id);

                        $this->info('Create Management Network Policy and Rules End', [
                            'network_id' => $managementNetwork->id,
                        ]);
                    }
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
}
