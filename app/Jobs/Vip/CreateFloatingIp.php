<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use App\Models\V2\FloatingIp;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateFloatingIp extends TaskJob
{
    use AwaitResources;

    /**
     * If a floating IP is requested, create one.
     * @return void
     */
    public function handle()
    {
        $vip = $this->task->resource;

        if (empty($this->task->data['allocate_floating_ip'])) {
            $this->info('No floating IP required, skipping');
            return;
        }

        if ($vip->ipAddress->floatingIpResource()->exists()) {
            $this->info('Floating IP ' . $vip->ipAddress->floatingIp->id . ' already assigned to the VIP, skipping');
            return;
        }

        if (empty($this->task->data['floating_ip_id'])) {
            $data = $this->task->data;

            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc_id = $vip->loadBalancerNetwork->network->router->vpc->id;
            $floatingIp->availability_zone_id = $vip->loadBalancerNetwork->network->availabilityZone->id;
            $floatingIp->syncSave();
            $this->info('Floating IP ' . $floatingIp->id . ' created for VIP ' . $vip->id);

            $data['floating_ip_id'] = $floatingIp->id;
            $this->task->setAttribute('data', $data)->saveQuietly();
        }

        if (!empty($this->task->data['floating_ip_id'])) {
            $this->awaitSyncableResources([$this->task->data['floating_ip_id']]);
        }
    }
}
