<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitFirewallPolicyRemoval extends Job
{
    use Batchable;

    public $tries = 30;
    public $backoff = 5;

    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->router->id]);

        if ($this->router->firewallPolicies()->count() > 0) {
            $this->router->firewallPolicies()->each(function ($fwp) {
                if ($fwp->sync->status == Sync::STATUS_FAILED) {
                    Log::error('Firewall policy in failed sync state, abort', ['id' => $this->router->id, 'fwp' => $fwp->id]);
                    $this->fail(new \Exception("Firewall policy '" . $fwp->id . "' in failed sync state"));
                    return;
                }
            });

            Log::warning($this->router->firewallPolicies()->count() . ' firewall polic(y/ies) still attached, retrying in ' . $this->backoff . ' seconds', ['id' => $this->router->id]);
            return $this->release($this->backoff);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->router->id]);
    }
}
