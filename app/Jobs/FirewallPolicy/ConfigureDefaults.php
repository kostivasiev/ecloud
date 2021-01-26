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

        foreach (config('firewall.policies') as $policy) {
            $firewallPolicy = app()->make(FirewallPolicy::class);
            $firewallPolicy->fill($policy);
            $firewallPolicy->router_id = $router->id;
            $firewallPolicy->save();

            foreach ($policy['rules'] as $rule) {
                $firewallRule = $firewallPolicy->firewallRules()->make($rule);
                $firewallRule->save();

                foreach ($rule['ports'] as $port) {
                    $firewallRulePort = $firewallRule->firewallRulePorts()->make($port);
                    $firewallRulePort->name = $firewallRulePort->id;
                    $firewallRulePort->save();
                }
            }
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
