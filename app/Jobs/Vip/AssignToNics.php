<?php

namespace App\Jobs\Vip;

use App\Jobs\Job;
use App\Jobs\Tasks\Nic\AssociateIp;
use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Models\V2\Vip;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AssignToNics extends Job
{
    use Batchable, LoggableModelJob, AwaitTask;

    private Task $task;

    private Vip $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $task->resource;
    }

    /**
     * Assign ip address to each of the load balancer NICs
     */
    public function handle()
    {
        $vip = $this->model;

        if (!$vip->ipAddress()->exists()) {
            $this->fail(new \Exception('Failed to assign VIP to NICs: VIP has no IP address.'));
            return false;
        }

        if (empty($this->task->data['task.' . AssociateIp::$name . '.ids'])) {
            $taskIds = [];

            Nic::where('network_id', '=', $vip->network_id)
                ->whereHas('instance.loadbalancer', function ($query) use ($vip) {
                $query->where('id', '=', $vip->loadbalancer->id);
            })->each(function ($nic) use ($vip, &$taskIds) {
                if (!$nic->ipAddresses()->where('id', $vip->ipAddress->id)->exists()) {
                    $task = $nic->createTaskWithLock(AssociateIp::$name, AssociateIp::class, ['ip_address_id' => $vip->ipAddress->id]);
                    $taskIds[] = $task->id;
                }
            });

            // TODO: create a new nic on the instance(s) (if the supplied network doesn't have a NIC already)

            if (!empty($taskIds)) {
                $this->task->data = [
                    'task.' . AssociateIp::$name . '.ids' => $taskIds,
                ];
                $this->task->saveQuietly();
            }
        } else {
            $taskIds = Task::whereIn('id', $this->task->data['task.' . AssociateIp::$name . '.ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }

        if ($taskIds) {
            $this->awaitTasks($taskIds);
        }
    }
}
