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


        if (empty($this->task->data['script_ids'])) {
            $instanceSoftware->software->scripts()->orderBy('sequence', 'asc')->each(function ($script) use ($instance) {
                $this->runScript($instance, $script);
            });
        }




        //  TODO: Need to update this to store in the task data the id of any completed scripts so that we can move on
        // to the next

        //$this->task->setAttribute('data', ['instance_software_ids' => $instanceSoftwareIds])->saveQuietly();



    }
}
