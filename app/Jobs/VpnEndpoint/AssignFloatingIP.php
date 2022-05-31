<?php

namespace App\Jobs\VpnEndpoint;

use App\Jobs\TaskJob;
use App\Jobs\Tasks\FloatingIp\Assign;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
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
        if (!isset($this->task->data[Assign::$name])) {
            $task = $floatingIp->createTaskWithLock(
                Assign::$name,
                Assign::class,
                ['resource_id' => $vpnEndpoint->id]
            );

            $this->info('Triggered ' . Assign::$name . ' task ' . $task->id . ' for Floating IP (' . $floatingIp->id . ')');
            $this->task->updateData(Assign::$name, $task->id);
        } else {
            $task = Task::findOrFail($this->task->data[Assign::$name]);
        }

        $this->awaitTaskWithRelease($task);
    }
}
