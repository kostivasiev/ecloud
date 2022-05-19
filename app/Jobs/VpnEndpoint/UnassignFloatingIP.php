<?php

namespace App\Jobs\VpnEndpoint;

use App\Jobs\TaskJob;
use App\Jobs\Tasks\FloatingIp\Unassign;
use App\Models\V2\Task;
use App\Traits\V2\TaskJobs\AwaitTask;

class UnassignFloatingIP extends TaskJob
{
    use AwaitTask;

    public function handle()
    {
        $vpnEndpoint = $this->task->resource;

        if (!isset($this->task->data['floatingip_detach_task_id'])) {
            if (!$vpnEndpoint->floatingIpResource()->exists()) {
                return;
            }

            $task = $vpnEndpoint->floatingIp->createTaskWithLock(
                Unassign::$name,
                Unassign::class
            );

            $this->info('Triggered floating_ip_unassign task ' . $task->id . ' for Floating IP (' . $vpnEndpoint->floatingIp->id . ')');
            $this->task->updateData('floatingip_detach_task_id', $task->id);
        } else {
            $task = Task::findOrFail($this->task->data['floatingip_detach_task_id']);
        }

        $this->awaitTaskWithRelease($task);
    }
}
