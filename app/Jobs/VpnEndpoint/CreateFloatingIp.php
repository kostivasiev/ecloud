<?php

namespace App\Jobs\VpnEndpoint;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Models\V2\VpnEndpoint;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateFloatingIp extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private Task $task;
    private VpnEndpoint $model;

    public function __construct(VpnEndpoint $vpnEndpoint, Task $task)
    {
        $this->task = $task;
        $this->model = $vpnEndpoint;
    }

    public function handle()
    {
        $vpnEndpoint = $this->model;
        $floatingIp = null;
        if (empty($this->task->data['floating_ip_id'])) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc()->associate($vpnEndpoint->vpnService->router->vpc);
            $floatingIp->availabilityZone()->associate($vpnEndpoint->vpnService->router->availabilityZone);
            $floatingIp->resource()->associate($this->model);
            $floatingIp->syncSave();

            // Add floating ip id to task data
            $this->task->data = [
                'floating_ip_id' => $floatingIp->id,
            ];
            $this->task->saveQuietly();
            Log::info(get_class($this) . ' : Floating IP ' . $floatingIp->id . 'created for VPN Endpoint ' . $vpnEndpoint->id);
        }
        if (!$floatingIp) {
            $floatingIp = FloatingIp::findOrFail($this->task->data['floating_ip_id']);
            if (empty($floatingIp->resource_id)) {
                Log::info(get_class($this) . ' : Existing Floating IP ' . $floatingIp->id . ' assigned to VPN Endpoint ' . $vpnEndpoint->id);
                $floatingIp->resource()->associate($vpnEndpoint);
                $floatingIp->syncSave();
            }
        }

        $this->awaitSyncableResources([
            $floatingIp->id,
        ]);
    }
}
