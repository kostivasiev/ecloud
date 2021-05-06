<?php

namespace App\Jobs\Sync\Instance;

use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\Undeploy\AwaitNicRemoval;
use App\Jobs\Instance\Undeploy\AwaitVolumeRemoval;
use App\Jobs\Instance\Undeploy\DeleteNics;
use App\Jobs\Instance\Undeploy\DeleteVolumes;
use App\Jobs\Instance\Undeploy\Undeploy;
use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;
use Illuminate\Support\Facades\Log;

class Delete extends Job
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
        $this->deleteTaskBatch([
            [
                new PowerOff($this->task->resource),
                new Undeploy($this->task->resource),
                new DeleteVolumes($this->task->resource),
                new DeleteNics($this->task->resource),
                new AwaitVolumeRemoval($this->task->resource),
                new AwaitNicRemoval($this->task->resource),
            ],
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
