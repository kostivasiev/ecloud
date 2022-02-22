<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use App\Models\V2\Credential;
use App\Models\V2\Instance;
use App\Traits\V2\TaskJobs\AwaitResources;
use Illuminate\Support\Facades\Log;

class DeployInstance extends TaskJob
{
    use AwaitResources;

    public function __construct($task)
    {
        parent::__construct($task);
        $this->tries = 180;
    }

    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        $instance = $loadBalancerNode->instance;
        $availabilityZone = $loadBalancerNode->loadBalancer->availabilityZone;

        if ($instance->deploy_data === null) {
            $instance->setAttribute('deploy_data', [])->saveQuietly();
        }

        if (empty($this->task->data['loadbalancer_instance_id'])) {
            $natsProxyIp = $this->getNatsProxyIp();
            if ($natsProxyIp === null) {
                $this->fail(new \Exception('No nats servers found for ' . $availabilityZone->id));
                return;
            }

            $managementGateway = null;
            try {
                $managementGateway = $this->getManagementGateway();
            } catch (\Exception $ex) {
                $this->fail($ex);
                return;
            }

            // Now populate the remaining deploy_data elements
            $deployData = $instance->deploy_data;
            $deployData['image_data'] = [
                    'stats_password' => $this->getStatsPassword(),
                    'nats_credentials' => decrypt($this->task->data['warden_credentials']),
                    'node_id' => $loadBalancerNode->node_id,
                    'group_id' => $loadBalancerNode->loadBalancer->config_id,
                    'nats_proxy_ip' => $natsProxyIp,
                    'primary' => false,
                    'keepalived_password' => $this->getKeepAliveDPassword(),
                    'management_gateway' => $managementGateway,
                    'management_subnet' => $this->getManagementSubnet(),
                ];
            $instance->deploy_data = $deployData;
            $instance->syncSave();
            $this->task->updateData('loadbalancer_instance_id', $instance->id);
        } else {
            $instance = Instance::where('id', '=', $this->task->data['loadbalancer_instance_id'])
                ->first();
        }
        $this->awaitSyncableResources([$instance->id]);
    }

    public function getStatsPassword()
    {
        $loadBalancerNode = $this->task->resource;
        return $loadBalancerNode->loadBalancer
            ->credentials()
            ->where('username', '=', 'ukfast_stats')
            ->first()
            ->password;
    }

    public function getKeepAliveDPassword()
    {
        $loadBalancerNode = $this->task->resource;
        return $loadBalancerNode->loadBalancer
            ->credentials()
            ->where('username', '=', 'keepalived')
            ->first()
            ->password;
    }

    public function getNatsProxyIp(): string
    {
        $loadBalancerNode = $this->task->resource;

        if ($loadBalancerNode->loadBalancer->vpc->advanced_networking) {
            return config('load-balancer.nats_proxy_ip.advanced');
        }

        return config('load-balancer.nats_proxy_ip.standard');
    }

    public function getManagementGateway(): string
    {
        $loadBalancerNode = $this->task->resource;
        $loadBalancer = $loadBalancerNode->loadBalancer;


        $managementRouter = $loadBalancer->vpc->routers()
            ->where('is_management', true)
            ->whereHas('availabilityZone', function ($query) use ($loadBalancer) {
                $query->where('id', $loadBalancer->availabilityZone->id);
            })->first();

        if (!$managementRouter) {
            Log::error('Failed to load management router', [
                'vpc_id' => $loadBalancer->vpc->id,
                'availability_zone_id' => $loadBalancer->availabilityZone->id,
                'load_balancer_id' => $loadBalancer->id]);

            throw new \Exception('Failed to load management router');
        }

        $managementNetwork = $managementRouter->networks->first();

        if (!$managementNetwork) {
            Log::error('Failed to load management network', [
                'vpc_id' => $loadBalancer->vpc->id,
                'availability_zone_id' => $loadBalancer->availabilityZone->id,
                'load_balancer_id' => $loadBalancer->id]);

            throw new \Exception('Failed to load management network');
        }

        return $managementNetwork->getGatewayAddress()->toString();
    }

    public function getManagementSubnet(): string
    {
        $loadBalancerNode = $this->task->resource;

        if ($loadBalancerNode->loadBalancer->vpc->advanced_networking) {
            return config('network.management_range.advanced');
        }

        return config('network.management_range.standard');
    }
}
