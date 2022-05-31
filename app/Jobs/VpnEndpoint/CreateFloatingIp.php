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

        if ($vpnEndpoint->floatingIpResource()->exists()) {
            $this->info('Floating IP already exists, skipping');
        }

        if (empty($this->task->data['floating_ip_id'])) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc()->associate($vpnEndpoint->vpnService->router->vpc);
            $floatingIp->availabilityZone()->associate($vpnEndpoint->vpnService->router->availabilityZone);
            $floatingIp->syncSave();

            // Add floating ip id to task data
            $this->task->updateData('floating_ip_id', $floatingIp->id);
            $this->info('Floating IP ' . $floatingIp->id . 'created for VPN Endpoint ' . $vpnEndpoint->id);
        }

        $this->awaitSyncableResources([
            $this->task->data['floating_ip_id'],
        ]);
    }
}
