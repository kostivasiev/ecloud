<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Router;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class ConfigureDefaultFirewallPolicies extends Job
{
    use Batchable, LoggableModelJob;

    private OrchestratorBuild $model;

    public function __construct(OrchestratorBuild $orchestratorBuild)
    {
        $this->model = $orchestratorBuild;
    }

    public function handle()
    {
        $orchestratorBuild = $this->model;

        $data = collect(json_decode($orchestratorBuild->orchestratorConfig->data));

        if (!$data->has('router')) {
            Log::info(get_class($this) . ' : OrchestratorBuild does not contain any routers, skipping', ['id' => $this->model->id]);
            return;
        }

        collect($data->get('router'))->each(function ($definition, $index) use ($orchestratorBuild) {
            if (isset($definition->configure_default_policies) && $definition->configure_default_policies === true) {
                $router = Router::findOrFail($orchestratorBuild->state['router'][$index]);

                $firewallPolicyIds = [];
                foreach (config('firewall.policies') as $policy) {
                    if (!$router->firewallPolicies()->where('name', $policy['name'])->exists()) {
                        $firewallPolicy = app()->make(FirewallPolicy::class);
                        $firewallPolicy->fill($policy);
                        $firewallPolicy->router_id = $router->id;
                        $firewallPolicy->save();

                        foreach ($policy['rules'] as $rule) {
                            $firewallRule = app()->make(FirewallRule::class);
                            $firewallRule->fill($rule);
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

                        $firewallPolicyIds[] = $firewallPolicy->id;

                        Log::info(get_class($this) . ' : OrchestratorBuild created firewall policy ' . $firewallPolicy->id, ['id' => $this->model->id]);
                    }

                    $orchestratorBuild->updateState('default_firewall_policies', $router->id, $firewallPolicyIds);
                }
            }
        });
    }
}
