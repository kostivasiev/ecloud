<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Loadbalancers\AdminClient;

class DeleteCluster extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private LoadBalancer $model;
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $task->resource;
    }

    public function handle()
    {
        $loadBalancer = $this->model;
        if ($loadBalancer->config_id === null) {
            Log::info('No Loadbalancer Cluster available, skipping', [
                'id' => $loadBalancer->id,
            ]);
            return;
        }
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadBalancer->getResellerId());
        $status = $client->clusters()->deleteById($loadBalancer->config_id);
        if ($status) {
            Log::info('Loadbalancer cluster has been deleted.', [
                'id' => $loadBalancer->id,
                'cluster_id' => $loadBalancer->config_id,
            ]);
        }
    }
}
