<?php

namespace App\Jobs\Sync\VpnSession;

use App\Jobs\Job;
use App\Jobs\Nsx\VpnSession\CreateVpnSession;
use App\Jobs\VpnSession\AwaitNetworkNoSNatSync;
use App\Jobs\VpnSession\AwaitSyncNetworkNoSNatsTasks;
use App\Jobs\VpnSession\CreateNetworkNoSNats;
use App\Jobs\VpnSession\CreatePreSharedKey;
use App\Jobs\VpnSession\SyncNetworkNoSNats;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->updateTaskBatch([
            [
                new CreatePreSharedKey($this->task->resource),
                new CreateVpnSession($this->task->resource),
                new SyncNetworkNoSNats($this->task, $this->task->resource),
                new AwaitSyncNetworkNoSNatsTasks($this->task, $this->task->resource)
            ],
        ])->dispatch();
    }
}