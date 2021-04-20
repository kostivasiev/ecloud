<?php

namespace App\Jobs\Router\Defaults;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConfigureFirewallPolicyDefaults extends Job
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

        if ($this->router->sync->status == Sync::STATUS_FAILED) {
            Log::error('Router in failed sync state, abort', ['id' => $this->router->id]);
            $this->fail(new \Exception("Router '" . $this->router->id . "' in failed sync state"));
            return;
        }

        if ($this->router->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Router not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->router->id]);
            return $this->release($this->backoff);
        }

        foreach (config('firewall.policies') as $policy) {
            Log::debug('FirewallPolicy', $policy);
            $firewallPolicy = app()->make(FirewallPolicy::class);
            $firewallPolicy->fill($policy);
            $firewallPolicy->router_id = $this->router->id;
            $firewallPolicy->save();

            Bus::batch([
                [
                    new AwaitFirewallPolicySync($firewallPolicy),
                    new CreateFirewallRules($firewallPolicy, $policy),
                ]
            ]
            )->catch(function (Batch $batch, Throwable $e) use ($firewallPolicy) {
                Log::error('Failed to configure default firewall policy', ['resource_id' => $firewallPolicy->id, 'exception' => $e->getMessage()]);
            }
            )->dispatch();
        }

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $this->router->id]);
    }
}
