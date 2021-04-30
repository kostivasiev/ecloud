<?php

namespace App\Jobs\Tasks;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AwaitTaskJob extends Job
{
    use Batchable;

    public $tries;
    public $backoff;

    private Task $task;

    public function __construct(Task $task, $tries = 60, $backoff = 5)
    {
        $this->task = $task;
        $this->tries = $tries;
        $this->backoff = $backoff;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->id]);

        if ($this->task->status == Task::STATUS_FAILED) {
            $this->fail(new \Exception("Task '" . $this->task->id . "' in failed state"));
            return;
        }

        if ($this->task->status == Task::STATUS_INPROGRESS) {
            Log::warning($this->task->id . ' in-progress, retrying in ' . $this->backoff . ' seconds', ['id' => $this->task->id]);
            return $this->release($this->backoff);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id]);
    }
}
