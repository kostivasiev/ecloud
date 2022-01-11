<?php

namespace App\Jobs\LoadBalancerNode;

use App\Jobs\Job;
use App\Models\V2\LoadBalancerNode;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use UKFast\Admin\Loadbalancers\AdminClient;

class GetWardenCredentials extends Job
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
        $loadBalancer = $this->model->loadBalancer;
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
