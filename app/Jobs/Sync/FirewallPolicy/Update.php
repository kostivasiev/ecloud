<?php

namespace App\Jobs\Task\FirewallPolicy;

use App\Jobs\Job;
use App\Jobs\FirewallPolicy\Deploy;
use App\Jobs\FirewallPolicy\DeployCheck;
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

        $this->updateTaskBatch([
            [
                new Deploy($this->task->resource),
                new DeployCheck($this->task->resource),
            ]
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
