<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use App\Jobs\Tasks\Nic\AssociateIp;
use App\Models\V2\Nic;
use App\Traits\V2\TaskJobs\AwaitTask;

class AssignToNics extends TaskJob
{
    use AwaitTask;

    /**
     * Assign VIP's IP address to each of the load balancer instances NICs with the same network as the Vip
     */
    public function handle()
    {
        $vip = $this->task->resource;

        if (!$vip->ipAddress()->exists()) {
            $this->fail(new \Exception('Failed to assign VIP to NICs: VIP has no IP address.'));
            return false;
        }

        $associateIpTasks = 'task.' . AssociateIp::$name . '.ids';

        if (empty($this->task->data[$associateIpTasks])) {
            $data = $this->task->data;
            $data[$associateIpTasks] = [];

            Nic::where('network_id', '=', $vip->network_id)
                ->whereHas('instance.loadbalancer', function ($query) use ($vip) {
                $query->where('id', '=', $vip->loadbalancer->id);
            })->each(function ($nic) use ($vip, &$data, $associateIpTasks) {
                if (!$nic->ipAddresses()->where('id', $vip->ipAddress->id)->exists()) {
                    $this->info('Assigning VIP ' . $vip->id . ' to NIC ' . $nic->id);
                    $task = $nic->createTaskWithLock(AssociateIp::$name, AssociateIp::class, ['ip_address_id' => $vip->ipAddress->id]);
                    $data[$associateIpTasks][] = $task->id;
                }
            });

            if (!empty($data[$associateIpTasks])) {
                $this->task->setAttribute('data', $data)->saveQuietly();
            }
        }

        if (!empty($data[$associateIpTasks])) {
            $this->awaitTasks($data[$associateIpTasks]);
        }
    }
}
