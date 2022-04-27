<?php

namespace App\Jobs\LoadBalancerNetwork;

use App\Jobs\TaskJob;
use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteNics extends TaskJob
{
    use AwaitResources;

    /**
     * Delete NICs from the load balancer notes for this network
     *
     * @see https://mgmt-20.ecloud-service.ukfast.co.uk:8443/swagger/ui/index#/VPC_Instance_v2/VPC_Instance_v2_RemoveNIC
     * @return void
     */
    public function handle()
    {
        $loadBalancerNetwork = $this->task->resource;

        Nic::where('network_id', '=', $loadBalancerNetwork->network->id)
            ->whereHas('instance.loadBalancerNode', function ($query) use ($loadBalancerNetwork) {
                $query->where('load_balancer_id', '=', $loadBalancerNetwork->loadbalancer->id);
            })->each(function ($nic) {
                if ($nic->ipAddresses()->withType(IpAddress::TYPE_CLUSTER)->exists()) {
                    $this->fail(new \Exception('Failed to delete NIC ' . $nic->id . ', ' . IpAddress::TYPE_CLUSTER . ' IP detected'));
                    return false;
                }

                $this->deleteResource($nic->id);

                if ($this->job->hasFailed() || $this->job->isReleased()) {
                    return false;
                }
            });
    }
}
