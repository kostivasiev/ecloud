<?php

namespace App\Jobs;

use App\Models\V2\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TestTaskChildJob extends Job
{
    protected $task;

    function __construct($task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        Log::info('TestTaskChildJob: Handling TestTaskChildJob');

        Log::info("TestTaskChildJob: dispatching child job 1");

        dispatch((new TestTaskChildJobChild($this->task))->chain(
            new TestTaskChildJobChild($this->task)
        ));

        Log::info("TestTaskChildJob: finished");
    }
}
