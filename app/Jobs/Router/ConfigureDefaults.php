<?php

namespace App\Jobs\Router;

use App\Jobs\FirewallPolicy\AwaitFirewallPolicySync;
use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Router;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConfigureDefaults extends Job
{
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $this->router->id]);

        foreach (config('firewall.policies') as $policy) {
            Log::debug('FirewallPolicy', $policy);
            $firewallPolicy = app()->make(FirewallPolicy::class);
            $firewallPolicy->fill($policy);
            $firewallPolicy->router_id = $this->router->id;
            $firewallPolicy->save();

            Bus::batch([
                new AwaitFirewallPolicySync($firewallPolicy),
            ])->then(function (Batch $batch) use ($firewallPolicy, $policy) {
                $firewallPolicy->withSyncLock(function ($firewallPolicy) use ($policy) {
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

                    $firewallPolicy->save();
                });
            })->catch(function (Batch $batch, Throwable $e) use ($firewallPolicy) {
                Log::error(get_class($this) . ' : Failed to configure default firewall policy', ['resource_id' => $firewallPolicy->id, 'exception' => $e->getMessage()]);
            })->dispatch();
        }

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $this->router->id]);
    }
}
