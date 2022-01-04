<?php

namespace App\Jobs\VpnEndpoint;

use App\Jobs\Job;
use App\Models\V2\VpnEndpoint;
use App\Support\Sync;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UnassignFloatingIP extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;
    
    private $model;

    public function __construct(VpnEndpoint $vpnEndpoint)
    {
        $this->model = $vpnEndpoint;
    }

    public function handle()
    {
        $vpnEndpoint = $this->model;

        if (!$vpnEndpoint->floatingIp()->exists()) {
            return;
        }

        $task = $vpnEndpoint->floatingIp->createTaskWithLock(
            'floating_ip_unassign',
            \App\Jobs\Tasks\FloatingIp\Unassign::class
        );
        Log::info('Triggered floating_ip_unassign task ' . $task->id . ' for Floating IP (' . $vpnEndpoint->floatingIp->id . ')');

        $this->awaitSyncableResources([
            $vpnEndpoint->floatingIp->id,
        ]);
    }
}
