<?php

namespace App\Traits\V2;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

trait TaskableBatch
{
    public function taskBatchExceptionCallback()
    {
        return function (Throwable $e) {
            return ($e instanceof RequestException && $e->hasResponse()) ?
                $e->getResponse()->getBody()->getContents() :
                $e->getMessage();
        };
    }

    // TODO: Rename to taskBatch
    public function updateTaskBatch($jobs, ...$callbacks)
    {
        $task = $this->task;
        $exceptionCallback = $this->taskBatchExceptionCallback();

        $batch = Bus::batch($jobs);
        foreach ($callbacks as $callback) {
            $batch->then($callback);
        }

        $batch->then(function (Batch $batch) use ($task) {
            Log::info("Setting task completed", ['id' => $task->id, 'resource_id' => $task->resource->id]);
            $task->completed = true;
            $task->save();
        })->catch(function (Batch $batch, Throwable $e) use ($task, $exceptionCallback) {
            Log::warning("Setting task failed", ['id' => $task->id, 'resource_id' => $task->resource->id]);
            $task->failure_reason = $exceptionCallback($e);
            $task->save();
        });

        return $batch;
    }

    // TODO: Move this to SyncableBatch trait, utilising renamed taskBatch function above
    public function deleteTaskBatch($jobs)
    {
        $task = $this->task;

        return $this->updateTaskBatch($jobs)->then(function (Batch $batch) use ($task) {
            $task->resource->delete();
        });
    }
}
