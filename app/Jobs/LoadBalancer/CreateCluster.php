<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\Credential;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Task;
use App\Services\V2\PasswordService;
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

    public function handle(PasswordService $passwordService)
    {
        $loadbalancer = $this->model;
        $passwordService->special = true;

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

        // Credentials
        $loadbalancer->credentials()->createMany([
            [
                'name' => 'keepalived',
                'host' => null,
                'username' => 'root',
                'password' => $passwordService->generate(16),
                'port' => null,
                'is_hidden' => true,
            ],
            [
                'name' => 'haproxy stats',
                'host' => null,
                'username' => 'root',
                'password' => $passwordService->generate(8),
                'port' => 8404,
                'is_hidden' => false,
            ]
        ]);
    }
}
