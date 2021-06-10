<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Router;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Support\Facades\Log;

class ConfigureRouterDefaults extends Job
{
    use LoggableModelJob;

    private $model;

    public $tries = 1;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    public function handle()
    {
        foreach (config('firewall.policies') as $policy) {
            Log::debug('FirewallPolicy', $policy);
            $firewallPolicy = app()->make(FirewallPolicy::class);
            $firewallPolicy->fill($policy);
            $firewallPolicy->router_id = $this->model->id;
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

            $firewallPolicy->syncSave();
        }
    }
}
