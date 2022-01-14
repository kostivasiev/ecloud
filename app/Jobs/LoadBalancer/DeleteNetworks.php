<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteNetworks extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private LoadBalancer $model;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancer = $this->model;

        if ($loadBalancer->loadBalancerNetworks()->count() == 0) {
            return;
        }

        if (empty($this->task->data['load_balancer_network_ids'])) {
            $data = $this->task->data;

            $loadBalancer->loadBalancerNetworks()->each(function ($loadBalancerNetwork) use (&$data, $loadBalancer) {
                Log::info(get_class($this) . ': Deleting load balancer network ' . $loadBalancerNetwork->id, ['id' => $loadBalancer->id]);
                $loadBalancerNetwork->syncDelete();
                $data['load_balancer_network_ids'][] = $loadBalancerNetwork->id;
            });

            $this->task->setAttribute('data', $data)->saveQuietly();
        }

        if (isset($this->task->data['load_balancer_network_ids'])) {
            $this->awaitSyncableResources($this->task->data['load_balancer_network_ids']);
        }
    }
}
