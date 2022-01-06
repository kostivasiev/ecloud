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
use UKFast\Admin\Loadbalancers\Entities\Cluster;

class CreateCluster extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private LoadBalancer $model;
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $loadbalancer = $this->model;

        if ($loadbalancer->config_id !== null) {
            Log::info('Loadbalancer has already been assigned a cluster id, skipping', [
                'id' => $loadbalancer->id,
                'cluster_id' => $loadbalancer->config_id,
            ]);
            return;
        }
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadbalancer->getResellerId());
        $response = $client->clusters()->createEntity(new Cluster([
            'name' => $loadbalancer->id,
            'internal_name' => $loadbalancer->id
        ]));
        Log::info('Setting Loadbalancer config id', [
            'id' => $loadbalancer->id,
            'cluster_id' => $response->getId(),
        ]);
        $loadbalancer->setAttribute('config_id', $response->getId())->saveQuietly();
    }
}
