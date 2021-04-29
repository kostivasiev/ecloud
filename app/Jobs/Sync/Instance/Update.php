<?php

namespace App\Jobs\Task\Instance;

use App\Jobs\Instance\ComputeUpdate;
use App\Jobs\Instance\Deploy\ActivateWindows;
use App\Jobs\Instance\Deploy\AssignFloatingIp;
use App\Jobs\Instance\Deploy\AttachOsDisk;
use App\Jobs\Instance\Deploy\AwaitNicTask;
use App\Jobs\Instance\Deploy\CheckNetworkAvailable;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Jobs\Instance\Deploy\ConfigureWinRm;
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
use App\Jobs\Instance\Deploy\Deploy;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;
use Illuminate\Support\Facades\Log;

class Update extends Job
{
    use TaskableBatch;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);

        if (!$this->task->resource->deployed) {
            $this->updateTaskBatch([
                [
                    new CheckNetworkAvailable($this->task->resource),
                    new Deploy($this->task->resource),
                    new PrepareOsDisk($this->task->resource),
                    new AttachOsDisk($this->task->resource),
                    new ConfigureNics($this->task->resource),
                    new AwaitNicTask($this->task->resource),
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

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
