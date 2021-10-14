<?php
namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateManagementNetworkPolicies extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private Task $task;
    private Router $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $router = $this->model;
        if ($router->vpc->advanced_networking) {
            if (!empty($this->task->data['management_router_id']) && !empty($this->task->data['management_network_id'])) {
                // need to check that the router & network are up and running
                $managementRouter = Router::find($this->task->data['management_router_id']);
                $managementNetwork = Network::find($this->task->data['management_network_id']);
                if ($managementRouter && $managementNetwork) {
                    $this->awaitSyncableResources([
                        $managementRouter->id,
                        $managementNetwork->id,
                    ]);
                }
                if ($managementNetwork) {
                    Log::info(get_class($this) . ' - Create Network Policy and Rules Start', [
                        'router_id' => $managementRouter->id,
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

                    Log::info(get_class($this) . ' - Create Network Policy and Rules End', [
                        'router_id' => $managementRouter->id,
                        'network_id' => $managementNetwork->id,
                    ]);
                }
            }
        }
    }
}
