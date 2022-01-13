<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\TaskJob;
use App\Models\V2\Credential;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeployInstance extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $loadBalancerNode = $this->task->resource;
        $instance = $loadBalancerNode->instance;
        if ($instance->deploy_data === null) {
            $instance->setAttribute('deploy_data', [])->saveQuietly();
        }

        if (empty($this->task->data['loadbalancer_instance_id'])) {
            // Now populate the remaining deploy_data elements
            $deployData = $instance->deploy_data + [
                    'stats_password' => $this->getStatsPassword(),
                    'nats_credentials' => decrypt($this->task->data['warden_credentials']),
                    'node_id' => $loadBalancerNode->node_id,
                    'group_id' => $loadBalancerNode->loadBalancer->config_id,
                    'nats_servers' => $this->getNatsServers(),
                    'primary' => false,
                    'keepalived_password' => $this->getKeepAliveDPassword()
                ];
            $instance->setAttribute('deploy_data', $deployData)->syncSave();
            $this->task->setAttribute('data', ['loadbalancer_instance_id' => $instance->id])->saveQuietly();
        } else {
            $this->awaitSyncableResources($this->task->data['loadbalancer_instance_id']);
        }
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

    public function getNatsServers(): array
    {
        $loadBalancerNode = $this->task->resource;
        $natsServer = 'lb_nats_server';
        if ($loadBalancerNode->loadBalancer->vpc->advanced_networking) {
            $natsServer.= '_advanced';
        }
        $cred = Credential::where([
                ['resource_id', '=', $loadBalancerNode->loadBalancer->availabilityZone->id],
                ['username', '=', $natsServer]
            ])
            ->first();
        return [$cred->host.':'.$cred->port];
    }
}
