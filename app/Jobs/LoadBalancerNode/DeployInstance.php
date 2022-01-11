<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\Job;
use App\Models\V2\LoadBalancerNode;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskJobs\AwaitResources;
use Illuminate\Bus\Batchable;

class DeployInstance extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private LoadBalancerNode $model;
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $loadBalancerNode = $this->model;
        $instance = $loadBalancerNode->instance;

        if (empty($this->task->data['loadbalancer_instance_id'])) {
            // Now populate the remaining deploy_data elements
            $deployData = $instance->deploy_data + [
                    'stats_password' => $this->getStatsPassword(),
                    'nats_credentials' => decrypt($this->task->data['warden_credentials']),
                    'node_id' => $loadBalancerNode->node_id,
                    'group_id' => $loadBalancerNode->loadBalancer->config_id,
                    'nats_servers' => ['tls://localhost:4222'],
                    'primary' => false,
                    'keepalived_password' => $this->getKeepAliveDPassword(),
                ];
            $instance->setAttribute('deploy_data', $deployData)->syncSave();
            $this->task->setAttribute('data', ['loadbalancer_instance_id' => $instance->id])->saveQuietly();
        } else {
            $this->awaitSyncableResources($this->task->data['loadbalancer_instance_id']);
        }
    }

    public function getStatsPassword()
    {
        return $this->model->loadBalancer
            ->credentials()
            ->where('username', '=', 'ukfast_stats')
            ->first()
            ->password;
    }

    public function getKeepAliveDPassword()
    {
        return $this->model->loadBalancer
            ->credentials()
            ->where('username', '=', 'keepalived')
            ->first()
            ->password;
    }
}
