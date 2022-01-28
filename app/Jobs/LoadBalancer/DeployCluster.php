<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;

class DeployCluster extends TaskJob
{
    public function handle()
    {
        $vip = $this->task->resource;
        $adminClient = app()->make(AdminClient::class)->setResellerId($vip->loadbalancer->getResellerId());
        $response = $adminClient->clusters()->deploy($vip->loadbalancer->config_id);
        if ($response->getStatusCode() != 204) {
            $errors = json_decode($response->getBody()->getContents())->errors;
            $this->fail(new \Exception('Loadbalancer configuration failed to deploy', [
                'errors' => $errors
            ]));
        }
        $this->info('Loadbalancer deployment started', [
            'load_balancer_id' => $vip->loadbalancer->id,
            'cluster_id' => $vip->loadbalancer->config_id,
        ]);
    }
}
