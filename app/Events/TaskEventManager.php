<?php

namespace App\Events;

use Carbon\Carbon;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Imtigger\LaravelJobStatus\EventManagers\EventManager;

// TaskEventManager is a reworked version of DefaultEventManager
// whilst waiting for this to be resolved: https://github.com/imTigger/laravel-job-status/issues/63
class TaskEventManager extends EventManager
{
    public function before(JobProcessing $event): void
    {
        $this->getUpdater()->update($event, [
            'status' => $this->getEntity()::STATUS_EXECUTING,
            'job_id' => $event->job->getJobId(),
            'queue' => $event->job->getQueue(),
            'started_at' => Carbon::now(),
        ]);
    }

    public function after(JobProcessed $event): void
    {
        if (!$event->job->hasFailed() && !$event->job->isReleased()) {
            $this->getUpdater()->update($event, [
                'status' => $this->getEntity()::STATUS_FINISHED,
                'finished_at' => Carbon::now(),
            ]);
        }
    }

    public function failing(JobFailed $event): void
    {
        $this->getUpdater()->update($event, [
            'status' => $this->getEntity()::STATUS_FAILED,
            'output' => ['failed_message' => $event->exception->getMessage()],
            'finished_at' => Carbon::now(),
        ]);
    }

    public function exceptionOccurred(JobExceptionOccurred $event): void
    {
        if (!$event->job->hasFailed()) {
            $this->getUpdater()->update($event, [
                'status' => $this->getEntity()::STATUS_RETRYING,
                'output' => ['exception_message' => $event->exception->getMessage()],
            ]);
        }
    }
}
