<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use App\Jobs\Tasks\FloatingIp\Assign;
use App\Models\V2\FloatingIp;
use App\Traits\V2\TaskJobs\AwaitTask;

class AssignFloatingIp extends TaskJob
{
    use AwaitTask;

    /**
     * Assign floating IP to the cluster IP of the VIP
     * @return void
     */
    public function handle()
    {
        $vip = $this->task->resource;

        if (empty($this->task->data['allocate_floating_ip'])) {
            $this->info('No floating IP required, skipping');
            return;
        }

        if ($vip->ipAddress->floatingIpResource()->exists()) {
            $this->info('Floating IP ' . $vip->ipAddress->floatingIpResource->floatingIp->id . ' already assigned to the VIP, skipping');
            return;
        }

        $floatingIp = FloatingIp::find($this->task->data['floating_ip_id']);
        if (!$floatingIp) {
            $this->fail(new \Exception('Failed to load floating IP for VIP ' . $vip->id));
            return;
        }

        $assignIpTask = 'task.' . Assign::TASK_NAME . '.ids';

        if (empty($this->task->data[$assignIpTask])) {
            $data = $this->task->data;

            $task = $floatingIp->createTaskWithLock(
                Assign::TASK_NAME,
                \App\Jobs\Tasks\FloatingIp\Assign::class,
                ['resource_id' => $vip->ipAddress->id]
            );

            $this->info('Assigning floating ' . $floatingIp->id . ' to cluster IP ' . $vip->ipAddress->id . ' for VIP ' . $vip->id);

            $data[$assignIpTask][] = $task->id;
            $this->task->setAttribute('data', $data)->saveQuietly();
        }

        if (!empty($this->task->data[$assignIpTask])) {
            $this->awaitTasks($this->task->data[$assignIpTask]);
        }
    }
}
