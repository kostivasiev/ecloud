<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Models\V2\VpnEndpoint;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateFloatingIp extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private Task $task;
    private VpnEndpoint $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $task->resource;
    }

    public function handle()
    {
        $floatingIp = null;
        // Create new floating ip if one not supplied
        if (empty($this->task->data['resource_id'])) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc_id = $this->task->data['vpc_id'];
            $floatingIp->syncSave();

            // Add floating ip id to task
            $this->task->data = [
                'resource_id' => $floatingIp->id,
            ];
            $this->task->saveQuietly();
        }
        if (!$floatingIp) {
            $floatingIp = FloatingIp::findOrFail($this->task->data['resource_id']);
        }

        $this->awaitSyncableResources([
            $floatingIp->id,
        ]);
    }
}
