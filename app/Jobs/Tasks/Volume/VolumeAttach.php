<?php

namespace App\Jobs\Tasks\Volume;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\Attach;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class VolumeAttach extends Job
{
    use Batchable, LoggableTaskJob;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $task = $this->task;
        $volume = $task->resource;
        $instance = Instance::findOrFail($task->data['instance_id']);

        Bus::batch([
            [
                new Attach($volume, $instance),
                new IopsChange($volume),
            ]
        ])->then(function (Batch $batch) use ($task) {
            Log::info("Setting task completed", ['id' => $task->id, 'resource_id' => $task->resource->id]);
            $task->completed = true;
            $task->save();
        })->catch(function (Batch $batch, Throwable $e) use ($task) {
            Log::warning("Setting task failed", ['id' => $task->id, 'resource_id' => $task->resource->id]);
            $task->failure_reason = $e->getMessage();
            $task->save();
        })->dispatch();
    }
}
