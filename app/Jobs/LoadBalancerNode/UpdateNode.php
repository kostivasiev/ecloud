<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use App\Models\V2\Nic;
use App\Traits\V2\TaskJobs\AwaitResources;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\Entities\Node;

class UpdateNode extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        if (!$loadBalancerNode->instance) {
            $this->info('No Instance found, skipping', [
                'load_balancer_node_id' => $loadBalancerNode->id,
            ]);
            return;
        }

        // now we need to get the ip address on the management nic
        $ipAddress = $this->getManagementNic()->ip_address;

        $client = app()->make(AdminClient::class)
            ->setResellerId($loadBalancerNode->loadBalancer->getResellerId());
        try {
            $client->nodes()->updateEntity(new Node([
                'id' => $loadBalancerNode->node_id,
                'ip_address' => $ipAddress,
            ]));
        } catch (\Exception $e) {
            $this->fail($e);
            return;
        }
        $this->info('Node updated with ip_address', [
            'node_id' => $loadBalancerNode->node_id,
            'ip_address' => $ipAddress,
        ]);
    }

    public function getManagementNic(): Nic
    {
        $loadBalancerNode = $this->task->resource;

        return Nic::whereHas('network.router', function ($query) {
            $query->whereIsManagement(true);
        })->with('instance', function ($query) use ($loadBalancerNode) {
            $query->where('instances.id', '=', $loadBalancerNode->instance_id);
        })->first();
    }

}
