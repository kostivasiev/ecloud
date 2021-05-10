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

    public function updateTaskBatch($jobs)
    {
        $task = $this->task;
        $callback = $this->taskBatchExceptionCallback();

        return Bus::batch($jobs)->then(function (Batch $batch) use ($task) {
            Log::info("Setting task completed", ['id' => $task->id, 'resource_id' => $task->resource->id]);
            $task->completed = true;
            $task->save();
        })->catch(function (Batch $batch, Throwable $e) use ($task, $callback) {
            Log::warning("Setting task failed", ['id' => $task->id, 'resource_id' => $task->resource->id]);
            $task->failure_reason = $callback($e);
            $task->save();
        });
    }

    public function deleteTaskBatch($jobs)
    {
        $task = $this->task;

        return $this->updateTaskBatch($jobs)->then(function (Batch $batch) use ($task) {
            $task->resource->delete();
        });
    }
}
