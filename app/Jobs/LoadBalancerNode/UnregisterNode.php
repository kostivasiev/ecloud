<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;

class UnregisterNode extends TaskJob
{
    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadBalancerNode->loadBalancer->getResellerId());
        try {
            $client->nodes()->deleteById($loadBalancerNode->node_id);
        } catch (\Exception $e) {
            $this->info('Unregistering instance as a loadbalancer node failed', [
                'id' => $loadBalancerNode->id,
                'instance_id' => $loadBalancerNode->instance_id,
                'load_balancer_id' => $loadBalancerNode->load_balancer_id,
            ]);
            return;
        }
        $this->info('Unregistering instance as loadbalancer node', [
            'id' => $loadBalancerNode->id,
            'instance_id' => $loadBalancerNode->instance_id,
            'cluster_id' => $loadBalancerNode->loadBalancer->config_id,
        ]);
        $loadBalancerNode->setAttribute('node_id', null)->saveQuietly();
    }
}
