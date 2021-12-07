<?php

namespace App\Jobs;

use App\Models\V2\Task;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

abstract class TaskJob extends Job
{
    use Batchable;

    public Task $task;

    public function __construct($task)
    {
        $this->task = $task;
    }

    protected function getTaskJobName()
    {
        return substr(strrchr(get_class($this), "\\"), 1);
    }

    public function trace($message, $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function debug($message, $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function info($message, $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function warning($message, $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function error($message, $context = [])
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    protected function log($level, $message, $context = [])
    {
        Log::log($level, $message, $this->hydrateLogContext($context));
    }

    protected function hydrateLogContext($context)
    {
        if (is_array($context)) {
            $context['task_id'] = $this->task->id;
            $context['task_name'] = $this->task->name;
            $context['task_job_name'] = $this->getTaskJobName();
            $context['resource_id'] = $this->task->resource()->withTrashed()->get()->first()->id;
        }

        return $context;
    }

    public function getLoggingData()
    {
        return $this->hydrateLogContext([]);
    }
}
