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
        $passwordService->special = true;

        if ($loadbalancer->config_id !== null) {
            $loadbalancer->credentials()->createMany([
                [
                    'name' => 'keepalived',
                    'host' => null,
                    'username' => 'keepalived',
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
}
