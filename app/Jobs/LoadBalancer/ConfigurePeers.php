<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;

class ConfigurePeers extends TaskJob
{
    public function handle()
    {
        if (empty($this->task->data['loadbalancer_node_ids'])) {
            $this->info('No nodes added, skipping');
            return;
        }

        $loadbalancer = $this->task->resource;
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadbalancer->getResellerId());
        $client->clusters()->configurePeers($loadbalancer->config_id);
    }
}
