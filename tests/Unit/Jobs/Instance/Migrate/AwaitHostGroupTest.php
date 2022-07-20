<?php

namespace Tests\Unit\Jobs\Instance\Migrate;

use App\Events\V2\Instance\Migrated;
use App\Jobs\Instance\Migrate\AwaitHostGroup;
use App\Models\V2\Task;
use App\Tasks\Instance\Migrate;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitHostGroupTest extends TestCase
{
    protected Task $task;

    public function testHostGroupInTaskDataSuccess()
    {
        Task::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'test-task',
                'name' => Migrate::$name,
                'job' => Migrate::class,
                'data' => [
                    'host_group_id' => $this->hostGroup()->id,
                ]
            ]);
            $this->task->resource()->associate($this->instanceModel());
            $this->task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class, Migrated::class]);

        dispatch(new AwaitHostGroup($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoHostGroupIdInTaskDataReleases()
    {
        Task::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'test-task',
                'name' => Migrate::$name,
                'job' => Migrate::class,
            ]);
            $this->task->resource()->associate($this->instanceModel());
            $this->task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class, Migrated::class]);

        dispatch(new AwaitHostGroup($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        Event::assertNotDispatched(JobFailed::class);
    }
}
