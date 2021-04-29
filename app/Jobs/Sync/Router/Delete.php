<?php

namespace App\Jobs\Sync\Router;

use App\Jobs\Job;
use App\Jobs\Router\AwaitFirewallPolicyRemoval;
use App\Jobs\Router\DeleteFirewallPolicies;
use App\Jobs\Router\UndeployRouterLocale;
use App\Jobs\Router\Undeploy;
use App\Jobs\Router\UndeployCheck;
use App\Models\V2\Sync;
use App\Models\V2\Task;
use App\Traits\V2\SyncableBatch;
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
                new DeleteFirewallPolicies($this->task->resource),
                new AwaitFirewallPolicyRemoval($this->task->resource),
                new UndeployRouterLocale($this->task->resource),
                new Undeploy($this->task->resource),
                new UndeployCheck($this->task->resource),
            ]
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
