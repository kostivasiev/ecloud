<?php

namespace App\Jobs\VpnEndpoint;

use App\Jobs\TaskJob;
use App\Jobs\Tasks\FloatingIp\Assign;
use App\Models\V2\FloatingIp;
use App\Traits\V2\TaskJobs\AwaitTask;

class AssignFloatingIP extends TaskJob
{
    use AwaitTask;

    public function handle()
    {
        $vpnEndpoint = $this->task->resource;

        if ($vpnEndpoint->floatingIpResource()->exists()) {
            $this->info('Floating IP is already assigned, skipping');
            return;
        }

        $floatingIp = FloatingIp::find($this->task->data['floating_ip_id']);
        if (empty($floatingIp)) {
            $this->fail(new \Exception('Failed to load floating IP ' . $this->task->data['floating_ip_id']));
            return;
        }
        if (!isset($this->task->data[Assign::TASK_NAME])) {
            $task = $floatingIp->createTaskWithLock(
                Assign::TASK_NAME,
                Assign::class,
                ['resource_id' => $vpnEndpoint->id]
            );

            $this->info('Triggered ' . Assign::TASK_NAME . ' task ' . $task->id . ' for Floating IP (' . $floatingIp->id . ')');
            $this->task->updateData(Assign::TASK_NAME, $task->id);
        }

        $this->awaitTasks([$this->task->data[Assign::TASK_NAME]]);
    }
}
