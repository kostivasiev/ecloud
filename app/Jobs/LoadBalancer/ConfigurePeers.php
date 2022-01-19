<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;

class ConfigurePeers extends TaskJob
{
    public function handle()
    {
        $loadbalancer = $this->task->resource;
        if ($loadbalancer->config_id === null) {
            $this->info('Loadbalancer has not been assigned a cluster id, skipping', [
                'cluster_id' => $loadbalancer->config_id,
            ]);
            return;
        }
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadbalancer->getResellerId());
        $response = $client->clusters()->configurePeers($loadbalancer->config_id);
        if (!$response) {
            $this->fail(new \Exception('Loadbalancer failed to configure it\'s peers'));
            return;
        }
    }
}