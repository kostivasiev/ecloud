<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\SDK\Exception\ApiException;

class UnassignFromLoadBalancerCluster extends TaskJob
{
    /**
     * Remove the VIP from the cluster via the load balancer API
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/loadbalancers/-/blob/master/openapi.yaml
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $vip = $this->task->resource;

        if (empty($vip->config_id)) {
            $this->fail(new \Exception('Failed to unassign VIP from load balancer cluster, no config_id was set.'));
            return;
        }

        $adminClient = app()->make(AdminClient::class)->setResellerId($vip->loadBalancerNetwork->loadbalancer->getResellerId());

        try {
            $adminClient->vips()->destroy($vip->config_id);
        } catch (ApiException $exception) {
            if ($exception->getStatusCode() != 404) {
                throw $exception;
            }
        }

        $this->info('VIP was removed from the load balancer cluster');
    }
}
