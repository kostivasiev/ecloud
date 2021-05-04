<?php

namespace App\Jobs\Sync\Router;

use App\Jobs\Job;
use App\Jobs\Router\AwaitFirewallPolicyRemoval;
use App\Jobs\Router\DeleteFirewallPolicies;
use App\Jobs\Router\Undeploy;
use App\Jobs\Router\UndeployCheck;
use App\Jobs\Router\UndeployRouterLocale;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use App\Traits\V2\SyncableBatch;

class Delete extends Job
{
    use SyncableBatch, JobModel;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        $this->deleteSyncBatch([
            [
                new DeleteFirewallPolicies($this->sync->resource),
                new AwaitFirewallPolicyRemoval($this->sync->resource),
                new UndeployRouterLocale($this->sync->resource),
                new Undeploy($this->sync->resource),
                new UndeployCheck($this->sync->resource),
            ]
        ])->dispatch();
    }
}
