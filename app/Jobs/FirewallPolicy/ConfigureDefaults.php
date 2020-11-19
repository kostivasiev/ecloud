<?php

namespace App\Jobs\FirewallPolicy;

use App\Events\V2\FirewallPolicy\Saved;
use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Router;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ConfigureDefaults extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $router = Router::findOrFail($this->data['router_id']);

        Model::withoutEvents(function () use ($router) {
            foreach (config('firewall.policies') as $policy) {
                $firewallPolicy = new FirewallPolicy();
                $firewallPolicy::addCustomKey($firewallPolicy);
                $firewallPolicy->fill($policy);
                $firewallPolicy->router_id = $router->getKey();
                $firewallPolicy->save();

                foreach ($policy['rules'] as $rule) {
                    $firewallRule = $firewallPolicy->firewallRules()->make($rule);
                    $firewallRule::addCustomKey($firewallRule);
                    $firewallRule->save();

                    foreach ($rule['ports'] as $port) {
                        $firewallRulePort = $firewallRule->firewallRulePorts()->make($port);
                        $firewallRulePort::addCustomKey($firewallRulePort);
                        $firewallRulePort->name = $firewallRulePort->getKey();
                        $firewallRulePort->save();
                    }
                }
                event(new Saved($firewallPolicy));
            }
        });

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
