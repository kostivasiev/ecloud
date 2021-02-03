<?php

namespace App\Jobs\FirewallPolicy;

use App\Events\V2\FirewallPolicy\Saved;
use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
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
            Log::debug('FirewallPolicy', $policy);
            $firewallPolicy = app()->make(FirewallPolicy::class);
            $firewallPolicy->fill($policy);
            $firewallPolicy->router_id = $router->id;
            $firewallPolicy->save();

            foreach ($policy['rules'] as $rule) {
                Log::debug('FirewallRule', $rule);
                $firewallRule = app()->make(FirewallRule::class);
                $firewallRule->fill($rule);
                $firewallRule->firewallPolicy()->associate($firewallPolicy);
                $firewallRule->save();

                foreach ($rule['ports'] as $port) {
                    Log::debug('FirewallRulePort', $port);
                    $firewallRulePort = app()->make(FirewallRulePort::class);
                    $firewallRulePort->fill($port);
                    $firewallRulePort->firewallRule()->associate($firewallRule);
                    $firewallRulePort->save();
                }
            }
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
