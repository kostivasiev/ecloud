<?php

namespace App\Jobs\Router\Defaults;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Router;
use App\Traits\V2\JobModel;
use Illuminate\Support\Facades\Log;

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
