<?php

namespace App\Jobs\Tasks\Instance;

use App\Jobs\Instance\PowerOff;
use App\Jobs\Instance\PowerOn;
use App\Jobs\Job;
use App\Jobs\Kingpin\Instance\MoveToHostGroup;
use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class HostGroupUpdate extends Job
{
    use Batchable, LoggableModelJob;

    public Task $task;
    private Instance $model;
    private $host_group_id;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
        $this->host_group_id = $this->task->data['host_group_id'];
    }

    public function handle()
    {
        $task = $this->task;
        $originalHostGroup = HostGroup::findOrFail($this->model->host_group_id);
        $newHostGroup = HostGroup::findOrFail($this->host_group_id);

        // Setup the jobs
        $jobs = [
            new MoveToHostGroup($this->model, $newHostGroup->id),
        ];

        // If hostSpec changes too, then we need to cyclePower on the instance
        if ($originalHostGroup->hostSpec->id != $newHostGroup->hostSpec->id) {
            array_unshift($jobs, new PowerOff($this->model));
            array_push($jobs, new PowerOn($this->model));
        }

        Bus::batch([
            $jobs
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
