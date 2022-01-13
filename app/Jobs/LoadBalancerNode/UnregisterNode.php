<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Loadbalancers\AdminClient;

class UnregisterNode extends TaskJob
{
    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        if ($loadBalancerNode->loadBalancer === null) {
            Log::info('UnregisterNode for ' . $loadBalancerNode->id . ', no loadbalancer so nothing to do');
            return;
        }
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadBalancerNode->loadBalancer->getResellerId());
        try {
            $client->nodes()->deleteById($loadBalancerNode->node_id);
        } catch (\Exception $e) {
            Log::info('Unregistering instance as a loadbalancer node failed', [
                'id' => $loadBalancerNode->id,
                'instance_id' => $loadBalancerNode->instance_id,
                'load_balancer_id' => $loadBalancerNode->load_balancer_id,
            ]);
            return;
        }
        Log::info('Unregistering instance as loadbalancer node', [
            'id' => $loadBalancerNode->id,
            'instance_id' => $loadBalancerNode->instance_id,
            'cluster_id' => $loadBalancerNode->loadBalancer->config_id,
        ]);
        $loadBalancerNode->setAttribute('node_id', null)->saveQuietly();
    }
}
