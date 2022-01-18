<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\Entities\Node;

class RegisterNode extends TaskJob
{
    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        if ($loadBalancerNode->loadBalancer->id === null) {
            Log::info('RegisterNode for ' . $loadBalancerNode->loadBalancer->id . ', no loadbalancer so nothing to do');
            return;
        }

        $client = app()->make(AdminClient::class)
            ->setResellerId($loadBalancerNode->loadBalancer->getResellerId());
        $response = $client->nodes()->createEntity(
            $loadBalancerNode->loadBalancer->config_id,
            new Node([
                'vendor_type' => 'eCloud',
                'vendor_id' => $loadBalancerNode->instance_id,
            ])
        );
        $this->info('Registering instance as loadbalancer node', [
                'cluster_id' => $loadBalancerNode->loadBalancer->config_id, 
                'node_id' => $response->getId(),
        ]);
        $loadBalancerNode->setAttribute('node_id', $response->getId())->saveQuietly();
    }
}
