<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UnassignFloatingIP extends Job
{
    use Batchable, LoggableModelJob, AwaitTask;

    private Task $task;
    private Instance $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $instance = $this->model;

        if (empty($this->task->data['task_ids'])) {
            $taskIds = [];

            $instance->nics()->each(function ($nic) use (&$taskIds) {
                $nic->ipAddresses()->each(function ($ipAddress) use (&$taskIds) {
                    if (!$ipAddress->floatingIpResource()->exists()) {
                        return;
                    }

                    $task = $ipAddress->floatingIpResource->floatingIp->createTaskWithLock(
                        'floating_ip_unassign',
                        \App\Jobs\Tasks\FloatingIp\Unassign::class
                    );

                    $taskIds[] = $task->id;

                    Log::info('Triggered floating_ip_unassign task for Floating IP (' . $ipAddress->floatingIpResource->floatingIp->id . ')');
                });
            });

            if (!empty($taskIds)) {
                $this->task->updateData('task_ids', $taskIds);
            }
        }

        if (!empty($this->task->data['task_ids'])) {
            $this->awaitTasks($this->task->data['task_ids']);
        }
    }
}
