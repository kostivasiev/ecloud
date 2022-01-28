<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use App\Models\V2\Nic;
use App\Traits\V2\TaskJobs\AwaitResources;
use GuzzleHttp\Exception\GuzzleException;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\Entities\TargetGroup;

class CreateTargetGroup extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadBalancerNode->loadBalancer->getResellerId());
        try {
            $response = $client->targetGroups()->createEntity(
                new TargetGroup([
                    'cluster_id' => $loadBalancerNode->loadbalancer->config_id,
                    'name' => $loadBalancerNode->name,
                    'balance' => 'roundrobin',
                    'mode' => 'http',
                    'close' => true,
                    'sticky' => true,
                    'cookie_opts' => 'rewrite secure',
                    'source' => $this->getManagementNic()->ip_address,
                    'timeouts_connect' => 10,
                    'timeouts_server' => 60,
                    'timeouts_http_request' => 5,
                    'timeouts_check' => 5,
                    'timeouts_tunnel' => 5,
                    'custom_options' => 'option splice-auto,option log-health-checks',
                    'monitor_url' => '/haproxy?monitor',
                    'monitor_method' => 'GET',
                    'monitor_host' => 'myhost.com',
                    'monitor_http_version' => 1.1,
                    'monitor_expect' => '1,3,5,7,9',
                    'monitor_tcp_monitoring' => true,
                    'check_port' => 8080,
                    'send_proxy' => true,
                    'send_proxy_v2' => true,
                    'ssl' => true,
                    'ssl_verify' => true,
                    'sni' => true,
                ])
            );
        } catch (GuzzleException $e) {
            $this->fail($e);
            return;
        }
        $loadBalancerNode->setAttribute('target_group_id', $response->getId())->saveQuietly();
        $this->info('Registering target group for load balancer instance', [
            'cluster_id' => $loadBalancerNode->loadBalancer->config_id,
            'loadbalancer_node_id' => $loadBalancerNode->id,
            'node_id' => $loadBalancerNode->node_id,
            'target_group_id' => $loadBalancerNode->target_group_id,
        ]);
    }

    public function getManagementNic(): Nic
    {
        $loadBalancerNode = $this->task->resource;

        return Nic::whereHas('network.router', function ($query) {
            $query->whereIsManagement(true);
        })->with('instance', function ($query) use ($loadBalancerNode) {
            $query->where('instances.id', '=', $loadBalancerNode->instance_id);
        })->first();
    }
}