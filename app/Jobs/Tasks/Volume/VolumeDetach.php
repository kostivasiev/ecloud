<?php

namespace App\Jobs\Tasks\Volume;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\Attach;
use App\Jobs\Kingpin\Volume\Detach;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Jobs\Sync\Completed;
use App\Models\V2\Instance;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class VolumeDetach extends Job
{
    use Batchable;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->resource->id]);

        $task = $this->task;
        $volume = $task->resource;
        $instance = Instance::findOrFail($task->data['instance_id']);

        Bus::batch([
            [
                new Detach($volume, $instance),
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

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->resource->id]);
    }
}
