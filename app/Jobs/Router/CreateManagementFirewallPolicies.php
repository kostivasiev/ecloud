<?php
namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CreateManagementFirewallPolicies extends Job
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
        if (!empty($this->task->data['management_router_id']) && !empty($this->task->data['management_network_id'])) {
            $firewallPolicy = false;
            if (empty($this->task->data['firewall_policy_id'])) {
                $managementRouter = Router::find($this->task->data['management_router_id']);
                if ($managementRouter) {
                    Log::info(get_class($this) . ' - Create Management Firewall Policy and Rules Start', [
                        'router_id' => $managementRouter->id,
                    ]);

                    $firewallPolicy = new FirewallPolicy([
                        'name' => 'Management_Firewall_Policy_for_' . $managementRouter->id,
                        'router_id' => $managementRouter->id,
                        'sequence' => 0,
                    ]);
                    $firewallPolicy->save();

                    // Allow inbound 4222
                    $firewallRule = new FirewallRule([
                        'name' => 'Allow_Ed_on_Port_4222_inbound_' . $managementRouter->id,
                        'sequence' => 10,
                        'firewall_policy_id' => $firewallPolicy->id,
                        'source' => 'ANY',
                        'destination' => 'ANY',
                        'action' => 'ALLOW',
                        'direction' => 'IN',
                        'enabled' => true
                    ]);
                    $firewallRule->save();
                    $firewallRulePort = new FirewallRulePort([
                        'firewall_rule_id' => $firewallRule->id,
                        'protocol' => 'TCP',
                        'source' => '4222',
                        'destination' => '4222'
                    ]);
                    $firewallRulePort->save();

                    // Block all outbound
                    (new FirewallRule([
                        'name' => 'Block_all_outbound_' . $managementRouter->id,
                        'sequence' => 10,
                        'firewall_policy_id' => $firewallPolicy->id,
                        'source' => 'ANY',
                        'destination' => 'ANY',
                        'action' => 'REJECT',
                        'direction' => 'OUT',
                        'enabled' => true
                    ]))->save();
                    $firewallPolicy->syncSave();

                    $this->task->data = Arr::add($this->task->data, 'firewall_policy_id', $firewallPolicy->id);
                    $this->task->saveQuietly();

                    Log::info(get_class($this) . ' - Create Firewall Policy and Rules End', [
                        'router_id' => $managementRouter->id,
                    ]);
                }
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
