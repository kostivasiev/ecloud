<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteCredentials extends Job
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
        $loadbalancer = $this->model;
        if ($loadbalancer->credentials()->count() == 0) {
            Log::info('No credentials to cleanup, skipping', ['id' => $loadbalancer->id]);
            return;
        }
        $loadbalancer->credentials->each(function ($credential) {
            $credential->delete();
        });
    }
}