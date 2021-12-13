<?php

namespace App\Jobs\InstanceSoftware;

use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\RunsScripts;
use App\Traits\V2\LoggableTaskJob;
use Illuminate\Bus\Batchable;

class RunScripts extends Job
{
    use Batchable, LoggableTaskJob, RunsScripts;

    private $task;

    public $tries = 120;

    public $backoff = 30;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $instanceSoftware = $this->task->resource;
        $instance = $instanceSoftware->instance;

            $completedScripts = $this->task->data['script_ids'] ?? [];

            //  TODO: Need to update this to store in the task data the id of any completed scripts so that we can move on
            $instanceSoftware->software->scripts()->orderBy('sequence', 'asc')->each(function ($script) use ($instance, &$completedScripts) {
                if (!in_array($script->id, $completedScripts)) {
                    $this->runScript($instance, $script);
                }

                $this->task->setAttribute('data', ['script_ids' => $completedScripts])->saveQuietly();
            });
    }
}
