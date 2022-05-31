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
            $instance->nics()->each(function ($nic) {
                $nic->ipAddresses()->each(function ($ipAddress) {
                    if ($ipAddress->floatingIpResource()->exists()) {
                        $taskIds[] = ($ipAddress->floatingIpResource->floatingIp->createTaskWithLock(
                            'floating_ip_unassign',
                            \App\Jobs\Tasks\FloatingIp\Unassign::class
                        ))->id;
                        Log::info('Triggered floating_ip_unassign task for Floating IP (' . $ipAddress->floatingIpResource->floatingIp->id . ')');
                        $this->task->updateData('task_ids', $taskIds);
                    }
                });
            });
        } else {
            $this->awaitTasks($this->task->data['task_ids']);
        }
    }
}
