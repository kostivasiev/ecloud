<?php

namespace App\Listeners\V2;

use App\Tasks\Task;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchTaskJob
{
    public function handle($event)
    {
        Log::debug(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model->job) {
            $task = $event->model;
            $taskJob = new $task->job($task);

            if ($taskJob instanceof Task) {
                $jobs = [];
                foreach ($taskJob->jobs() as $job) {
                    $jobs[] = new $job($task);
                }

                if (count($jobs) < 1) {
                    Log::info("Setting task completed (no jobs to execute)", ['task_id' => $task->id, 'resource_id' => $task->resource->id]);
                    $task->completed = true;
                    $task->save();
                    return;
                }

                $failureReasonCallback = $taskJob->failureReason();

                Log::debug(get_class($this) . " : Dispatching batch", ['task_id' => $task->id, 'resource_id' => $task->resource->id]);
                Bus::batch([$jobs])->then(function (Batch $batch) use ($task) {
                    Log::info("Setting task completed", ['task_id' => $task->id, 'resource_id' => $task->resource->id]);
                    $task->completed = true;
                    $task->save();
                })->catch(function (Batch $batch, Throwable $e) use ($task, $failureReasonCallback) {
                    Log::warning("Setting task failed", ['task_id' => $task->id, 'resource_id' => $task->resource->id]);
                    $task->failure_reason = $failureReasonCallback($e);
                    $task->save();
                })->dispatch();
            } else {
                Log::debug(get_class($this) . " : Dispatching job", ["job" => $event->model->job]);
                dispatch($taskJob);
            }
        } else {
            Log::debug(get_class($this) . " : Skipping job dispatch, no job defined for task", ["job" => $event->model->job]);
        }

        Log::debug(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
