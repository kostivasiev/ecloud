<?php

namespace App\Jobs\VpnEndpoint;

use App\Jobs\TaskJob;
use App\Models\V2\FloatingIp;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateFloatingIp extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $vpnEndpoint = $this->task->resource;

        $floatingIp = null;
        if (empty($this->task->data['floating_ip_id'])) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc()->associate($vpnEndpoint->vpnService->router->vpc);
            $floatingIp->availabilityZone()->associate($vpnEndpoint->vpnService->router->availabilityZone);
            $floatingIp->resource()->associate($vpnEndpoint);
            $floatingIp->syncSave();

            // Add floating ip id to task data
            $this->task->data = [
                'floating_ip_id' => $floatingIp->id,
            ];
            $this->task->saveQuietly();
            $this->info('Floating IP ' . $floatingIp->id . 'created for VPN Endpoint ' . $vpnEndpoint->id);
        }
        if (!$floatingIp) {
            $floatingIp = FloatingIp::findOrFail($this->task->data['floating_ip_id']);
            if (empty($floatingIp->resource_id)) {
                $this->info('Existing Floating IP ' . $floatingIp->id . ' assigned to VPN Endpoint ' . $vpnEndpoint->id);
                $floatingIp->resource()->associate($vpnEndpoint);
                $floatingIp->syncSave();
            }
        }

        $this->awaitSyncableResources([
            $floatingIp->id,
        ]);
    }
}
