<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use App\Models\V2\Nic;
use App\Tasks\Nic\DisassociateIp;
use App\Traits\V2\TaskJobs\AwaitTask;

class UnassignFromNics extends TaskJob
{
    use AwaitTask;

    /**
     * Un-assign VIP's cluster IP address from each of the load balancer instances NICs (with the same network as the Vip)
     */
    public function handle()
    {
        $vip = $this->task->resource;

        $disassociateIpTasks = 'task.' . DisassociateIp::$name . '.ids';

        if (empty($this->task->data[$disassociateIpTasks])) {
            $taskIds = [];

            Nic::where('network_id', '=', $vip->network_id)
                ->whereHas('instance.loadBalancerNode', function ($query) use ($vip) {
                    $query->where('load_balancer_id', '=', $vip->loadbalancer->id);
                })->each(function ($nic) use ($vip, &$taskIds) {
                    if ($nic->ipAddresses()->where('id', $vip->ipAddress->id)->exists()) {
                        $this->info('Unassigning VIP ' . $vip->id . ' from NIC ' . $nic->id);
                        $task = $nic->createTaskWithLock(
                            DisassociateIp::$name,
                            DisassociateIp::class,
                            ['ip_address_id' => $vip->ipAddress->id]
                        );
                        $taskIds[] = $task->id;
                    }
                });

            if (!empty($taskIds)) {
                $this->task->updateData($disassociateIpTasks, $taskIds);
            }
        }

        if (!empty($this->task->data[$disassociateIpTasks])) {
            $this->awaitTasks($this->task->data[$disassociateIpTasks]);
        }
    }
}
