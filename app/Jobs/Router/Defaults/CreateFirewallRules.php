<?php

namespace App\Jobs\Router\Defaults;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateFirewallRules extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 1;

    private $model;
    private $policy;

    public function __construct(FirewallPolicy $firewallPolicy, $policy)
    {
        $this->model = $firewallPolicy;
        $this->policy = $policy;
    }

    public function handle()
    {
        $policy = $this->policy;

        $this->model->withTaskLock(function ($firewallPolicy) use ($policy) {
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

            $this->model->save();
        });
    }
}
