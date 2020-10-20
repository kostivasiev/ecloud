<?php

namespace App\Jobs;

use App\Models\V2\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Imtigger\LaravelJobStatus\Trackable;

abstract class TaskJob extends \App\Jobs\Job {
    use Trackable;

    protected $task;

    public function __construct(Task $task = null)
    {
        if ($task) {
            $this->prepareStatus();
            $this->update(['task_id' => $task->getKey()]);
            $this->task = $task;
        }
    }
}