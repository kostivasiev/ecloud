<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\SDK\Exception\ApiException;

class UnregisterNode extends TaskJob
{
    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancerNode = $this->task->resource;

        if (empty($this->task->data['load_balancer_node_id'])) {
            $client = app()->make(AdminClient::class)
                ->setResellerId($loadBalancerNode->loadBalancer->getResellerId());
            try {
                $client->nodes()->deleteById($loadBalancerNode->node_id);
            } catch (ApiException $e) {
                if ($e->getStatusCode() !== 404) {
                    throw $e;
                }
                $this->info('Loadbalancer node not found, skipping', [
                    'id' => $loadBalancerNode->id,
                    'instance_id' => $loadBalancerNode->instance_id,
                    'cluster_id' => $loadBalancerNode->loadBalancer->config_id,
                ]);
                return;
            }
            $this->info('Unregistered instance as loadbalancer node', [
                'id' => $loadBalancerNode->id,
                'instance_id' => $loadBalancerNode->instance_id,
                'cluster_id' => $loadBalancerNode->loadBalancer->config_id,
            ]);
            $this->task->updateData('load_balancer_node_id', $loadBalancerNode->node_id);
        }
    }
}
