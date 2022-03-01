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

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $instanceSoftware = $this->task->resource;
        $instance = $instanceSoftware->instance;

        $completedScripts = $this->task->data['script_ids'] ?? [];

        $scripts = $instanceSoftware->software->scripts()->orderBy('sequence', 'asc')->get();

        foreach ($scripts as $script) {
            if (!in_array($script->id, $completedScripts)) {
                if (!$this->runScript($instance, $script)) {
                    return;
                }
                $this->task->updateData('script_ids', $completedScripts);
            }
        }
    }
}
