<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\SDK\Exception\ApiException;

class DeployCluster extends TaskJob
{
    public function handle()
    {
        $vip = $this->task->resource;
        $loadBalancer = $vip->loadBalancerNetwork->loadBalancer;

        $adminClient = app()->make(AdminClient::class)->setResellerId($loadBalancer->getResellerId());

        try {
            $cluster = $adminClient->clusters()->getById($loadBalancer->config_id);
        } catch (ApiException $exception) {
            if ($exception->getStatusCode() != 404) {
                throw $exception;
            }
            $this->info('Loadbalancer cluster not found, skipping', [
                'load_balancer_id' => $loadBalancer->id,
                'cluster_id' => $loadBalancer->config_id,
            ]);
            return;
        }

        // If the cluster has been deployed then we can deploy the changes
        if ($cluster->deployed_at === null) {
            $this->info('Loadbalancer not yet deployed, skipping update', [
                'load_balancer_id' => $loadBalancer->id,
                'cluster_id' => $loadBalancer->config_id,
            ]);
            return;
        }

        $this->info('Loadbalancer deployed, starting update', [
            'load_balancer_id' => $loadBalancer->id,
            'cluster_id' => $loadBalancer->config_id,
        ]);

        $adminClient->clusters()->deploy($loadBalancer->config_id);

        $this->info('Loadbalancer deployment completed', [
            'load_balancer_id' => $loadBalancer->id,
            'cluster_id' => $loadBalancer->config_id,
        ]);
    }
}
