<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\Entities\Cluster;

class CreateCluster extends TaskJob
{
    public function handle()
    {
        $loadbalancer = $this->task->resource;

        if ($loadbalancer->config_id !== null) {
            $this->info('Loadbalancer has already been assigned a cluster id, skipping', [
                'cluster_id' => $loadbalancer->config_id,
            ]);
            return;
        }
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadbalancer->getResellerId());
        $response = $client->clusters()->createEntity(new Cluster([
            'name' => $loadbalancer->id,
            'internal_name' => $loadbalancer->id
        ]));
        $this->info('Setting Loadbalancer config id', ['cluster_id' => $response->getId()]);
        $loadbalancer->setAttribute('config_id', $response->getId())->saveQuietly();
    }
}
