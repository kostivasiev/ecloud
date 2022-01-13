<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;

class GetWardenCredentials extends TaskJob
{
    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        $loadBalancer = $loadBalancerNode->loadBalancer;
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadBalancer->getResellerId());
        $response = $client->clusters()->get(
            vsprintf(
                'v2/clusters/%d/warden-credentials',
                $loadBalancer->config_id
            )
        );
        $wardenCredentials = (json_decode($response->getBody()->getContents()))->data->warden_credentials;
        if ($this->task->data === null) {
            $this->task->data = [];
        }
        $this->task
            ->setAttribute('data', $this->task->data + [
                'warden_credentials' => encrypt($wardenCredentials)
            ])->saveQuietly();
    }
}
