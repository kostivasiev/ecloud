<?php

namespace App\Jobs\Tasks\Nic;

use App\Jobs\Job;
use App\Models\V2\IpAddress;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;

class DisassociateIp extends Job
{
    use TaskableBatch, Batchable, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $task = $this->task;
        $nic = $task->resource;
        $ipAddress = IpAddress::findOrFail($task->data['ip_address_id']);

        $this->updateTaskBatch([
            [
                new \App\Jobs\Nsx\Nic\UnbindIpAddress($nic, $ipAddress)
            ]
        ])->dispatch();
    }
}