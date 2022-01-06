<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Task;
use App\Services\V2\PasswordService;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class CreateCredentials extends Job
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

        $loadbalancer->credentials()->firstOrCreate(
            ['username' => 'keepalived'],
            [
                'name' => 'keepalived',
                'host' => null,
                'password' => $passwordService->generate(8),
                'port' => null,
                'is_hidden' => true,
            ]
        );

        $loadbalancer->credentials()->firstOrCreate(
            ['username' => 'ukfast_stats'],
            [
                'name' => 'haproxy stats',
                'host' => null,
                'password' => $passwordService->generate(),
                'port' => 8090,
                'is_hidden' => true,
            ]
        );
    }
}
