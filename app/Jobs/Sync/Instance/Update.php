<?php

namespace App\Jobs\Sync\Instance;

use App\Jobs\Instance\ComputeUpdate;
use App\Jobs\Instance\Deploy\ActivateWindows;
use App\Jobs\Instance\Deploy\AssignFloatingIp;
use App\Jobs\Instance\Deploy\AwaitVolumeSync;
use App\Jobs\Instance\Deploy\AwaitNicSync;
use App\Jobs\Instance\Deploy\CheckNetworkAvailable;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Jobs\Instance\Deploy\ConfigureWinRm;
use App\Jobs\Instance\Deploy\Deploy;
use App\Jobs\Instance\Deploy\DeployCompleted;
use App\Jobs\Instance\Deploy\ExpandOsDisk;
use App\Jobs\Instance\Deploy\OsCustomisation;
use App\Jobs\Instance\Deploy\PrepareOsDisk;
use App\Jobs\Instance\Deploy\PrepareOsUsers;
use App\Jobs\Instance\Deploy\RunApplianceBootstrap;
use App\Jobs\Instance\Deploy\RunBootstrapScript;
use App\Jobs\Instance\Deploy\UpdateNetworkAdapter;
use App\Jobs\Instance\Deploy\WaitOsCustomisation;
use App\Jobs\Instance\PowerOn;
use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Update extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        if (!$this->task->resource->deployed) {
            $this->updateTaskBatch([
                [
                    new CheckNetworkAvailable($this->task->resource),
                    new Deploy($this->task->resource),
                    new PrepareOsDisk($this->task->resource),
                    new AwaitVolumeSync($this->task->resource),
                    new ConfigureNics($this->task->resource),
                    new AwaitNicSync($this->task->resource),
                    new AssignFloatingIp($this->task->resource),
                    new UpdateNetworkAdapter($this->task->resource),
                    new OsCustomisation($this->task->resource),
                    new PowerOn($this->task->resource),
                    new WaitOsCustomisation($this->task->resource),
                    new PrepareOsUsers($this->task->resource),
                    new ExpandOsDisk($this->task->resource),
                    new ConfigureWinRm($this->task->resource),
                    new ActivateWindows($this->task->resource),
                    new RunApplianceBootstrap($this->task->resource),
                    new RunBootstrapScript($this->task->resource),
                    new DeployCompleted($this->task->resource),
                ],
            ])->dispatch();
        } else {
            $this->updateTaskBatch([
                [
                    new ComputeUpdate($this->task->resource),
                ]
            ])->dispatch();
        }
    }
}
