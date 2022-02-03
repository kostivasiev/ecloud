<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;

class UnregisterNode extends TaskJob
{
    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        $success = false;

        if (empty($this->task->data['load_balancer_node_id'])) {
            $client = app()->make(AdminClient::class)
                ->setResellerId($loadBalancerNode->loadBalancer->getResellerId());
            try {
                $success = $client->nodes()->deleteById($loadBalancerNode->node_id);
            } catch (\Exception $e) {
                if ($e->getCode() !== 404) {
                    throw $e;
                }
                $this->info('Loadbalancer node not found, skipping', [
                    'id' => $loadBalancerNode->id,
                    'instance_id' => $loadBalancerNode->instance_id,
                    'cluster_id' => $loadBalancerNode->loadBalancer->config_id,
                ]);
                return;
            }
            if (!$success) {
                $this->fail(new \Exception('Failed to unregister loadbalancer node', [
                    'id' => $loadBalancerNode->id,
                    'instance_id' => $loadBalancerNode->instance_id,
                    'cluster_id' => $loadBalancerNode->loadBalancer->config_id,
                ]));
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
