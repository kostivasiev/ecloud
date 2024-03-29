<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use App\Jobs\Tasks\FloatingIp\Unassign;
use App\Traits\V2\TaskJobs\AwaitTask;

class UnassignFloatingIp extends TaskJob
{
    use AwaitTask;

    /**
     * Un-assign floating IP from the cluster IP of the VIP
     * @return void
     */
    public function handle()
    {
        $vip = $this->task->resource;

        if (!$vip->ipAddress->floatingIpResource()->exists()) {
            $this->info('No floating IP assigned to the VIP, skipping');
            return;
        }

        $floatingIp = $vip->ipAddress->floatingIpResource->floatingIp;

        $unassignIpTask = 'task.' . Unassign::TASK_NAME . '.id';

        if (empty($this->task->data[$unassignIpTask])) {
            $this->task->updateData(
                $unassignIpTask,
                ($floatingIp->createTaskWithLock(Unassign::TASK_NAME, Unassign::class))->id
            );

            $this->info('Unassigning floating IP ' . $floatingIp->id . ' from cluster IP ' . $vip->ipAddress->id . ' for VIP ' . $vip->id);
        }

        if (!empty($this->task->data[$unassignIpTask])) {
            $this->awaitTasks([$this->task->data[$unassignIpTask]]);
        }
    }
}
