<?php

namespace App\Jobs\Sync\VpnSession;

use App\Jobs\Job;
use App\Jobs\Nsx\VpnSession\Undeploy;
use App\Jobs\Nsx\VpnSession\UndeployCheck;
use App\Jobs\VpnSession\AwaitSyncNetworkNoSNatsTasks;
use App\Jobs\VpnSession\DeletePreSharedKey;
use App\Jobs\VpnSession\RemoveNetworks;
use App\Jobs\VpnSession\SyncNetworkNoSNats;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $this->deleteTaskBatch([
            [
                new Undeploy($this->task->resource),
                new UndeployCheck($this->task->resource),
                new RemoveNetworks($this->task->resource),
                new SyncNetworkNoSNats($this->task, $this->task->resource),
                new AwaitSyncNetworkNoSNatsTasks($this->task, $this->task->resource),
                new DeletePreSharedKey($this->task->resource)
            ]
        ])->dispatch();
    }
}
