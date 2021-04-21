<?php

namespace App\Jobs\Router\Defaults;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateFirewallRules extends Job
{
    use Batchable;

    public $tries = 1;

    private $firewallPolicy;
    private $policy;

    public function __construct(FirewallPolicy $firewallPolicy, $policy)
    {
        $this->firewallPolicy = $firewallPolicy;
        $this->policy = $policy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->firewallPolicy->id]);

        $policy = $this->policy;

        $this->firewallPolicy->withSyncLock(function ($firewallPolicy) use ($policy) {
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

            $this->firewallPolicy->save();
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->firewallPolicy->id]);
    }
}
