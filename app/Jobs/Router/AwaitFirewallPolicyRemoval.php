<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Support\Sync;

class AwaitFirewallPolicyRemoval extends TaskJob
{
    public $tries = 30;
    public $backoff = 5;

    public function handle()
    {
        $router = $this->task->resource;

        if ($router->firewallPolicies()->count() > 0) {
            foreach ($router->firewallPolicies as $firewallPolicy) {
                if ($firewallPolicy->sync->status == Sync::STATUS_FAILED) {
                    $this->fail(new \Exception("Firewall policy '" . $firewallPolicy->id . "' in failed sync state"));
                    return;
                }
            }

            $this->warning($router->firewallPolicies()->count() . ' firewall polic(y/ies) still attached, retrying in ' . $this->backoff . ' seconds');
            $this->release($this->backoff);
        }

        $this->info('No firewall policies found');
    }
}
