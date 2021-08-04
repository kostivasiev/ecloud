<?php

namespace App\Jobs\Sync\VpnSession;

use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use App\Traits\V2\TaskableBatch;

class Delete extends Job
{
    use TaskableBatch, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        // @todo - See https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/918
        $this->task->completed = true;
        $this->task->save();
    }
}
