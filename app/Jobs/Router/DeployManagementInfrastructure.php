<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Tasks\Vpc\CreateManagementInfrastructure;
use App\Traits\V2\TaskJobs\AwaitTask;

class DeployManagementInfrastructure extends TaskJob
{
    use AwaitTask;

    public function handle()
    {
        $router = $this->task->resource;
        if ($router->isManaged()) {
            return;
        }

        $taskIdKey = 'task.' . CreateManagementInfrastructure::$name . '.id';

        if (empty($this->task->data[$taskIdKey])) {
            $task = $router->vpc->createTask(
                CreateManagementInfrastructure::$name,
                CreateManagementInfrastructure::class,
                [
                    'availability_zone_id' => $router->availability_zone_id
                ]
            );

            $this->task->updateData($taskIdKey, [$task->id]);
        }

        if (isset($this->task->data[$taskIdKey])) {
            $this->awaitTasks($this->task->data[$taskIdKey]);
        }
    }
}
