<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\Entities\Vip;

class AssignToLoadBalancerCluster extends TaskJob
{
    /**
     * Assign the VIP to the cluster via the load balancer API
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/loadbalancers/-/blob/master/openapi.yaml
     * @return void
     */
    public function handle()
    {
        $vip = $this->task->resource;

        $loadBalancer = $vip->loadBalancerNetwork->loadbalancer;

        $adminClient = app()->make(AdminClient::class)->setResellerId($loadBalancer->getResellerId());

        $vipEntity = app()->make(Vip::class);

        $vipEntity->internalCidr = $vip->ipAddress->getIPAddress() . '/' . $vip->loadBalancerNetwork->network->getNetworkPrefix();

        if ($vip->ipAddress->floatingIp()->exists()) {
            // convert to CIDR format /32 is 1 IP address in subnet
            $vipEntity->externalCidr = $vip->ipAddress->floatingIp->getIPAddress(). '/32';
        }

        $response = $adminClient->vips()->createEntity($loadBalancer->config_id, $vipEntity);

        $vip->setAttribute('config_id', $response->getId())->save();

        $this->info('VIP was assigned to the load balancer cluster with ID: ' . $response->getId());
    }
}
