<?php

namespace App\Jobs\Router\Defaults;

use App\Jobs\Job;
use App\Jobs\Vpc\Defaults\AwaitRouterSync;
use App\Jobs\Vpc\Defaults\CreateNetwork;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConfigureRouterDefaults extends Job
{
    use JobModel;

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

            dispatch((new AwaitFirewallPolicySync($firewallPolicy))->chain([
                new CreateFirewallRules($firewallPolicy, $policy),
            ]));
        }
    }
}
