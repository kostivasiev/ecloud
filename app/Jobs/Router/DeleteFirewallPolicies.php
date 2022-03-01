<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;

class DeleteFirewallPolicies extends TaskJob
{
    public function handle()
    {
        foreach ($this->task->resource->firewallPolicies as $fwp) {
            $this->info("Triggering delete for firewall policy " . $fwp->id);
            $fwp->syncDelete();
        }
    }
}
