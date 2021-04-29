<?php

namespace App\Jobs\Router\Defaults;

use App\Jobs\Job;
use App\Jobs\Vpc\Defaults\AwaitRouterSync;
use App\Jobs\Vpc\Defaults\CreateNetwork;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Router;
use App\Support\Sync;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConfigureRouterDefaults extends Job
{
    private $router;

    public $tries = 1;

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

            dispatch((new AwaitFirewallPolicySync($firewallPolicy))->chain([
                new CreateFirewallRules($firewallPolicy, $policy),
            ]));
        }

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $this->router->id]);
    }
}
