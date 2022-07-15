<?php

namespace Tests\Unit\Jobs\Instance\Migrate;

use App\Jobs\Instance\Migrate\PowerOff;
use App\Models\V2\HostSpec;
use App\Models\V2\Task;
use App\Tasks\Instance\Migrate;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use App\Jobs\Instance\PowerOff as InstancePowerOff;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PowerOffTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

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
    }

    public function testDifferentHostSpecPowersOff()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        Queue::fake([InstancePowerOff::class]);

        $this->sharedHostGroup()->hostSpec()->associate(HostSpec::factory()->create([
            'id' => 'hs-2',
        ]))->save();

        dispatch(new PowerOff($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Queue::assertPushed(InstancePowerOff::class);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSameHostSpecDoesNotPowerOff()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);
        Queue::fake([InstancePowerOff::class]);

        dispatch(new PowerOff($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Queue::assertNotPushed(InstancePowerOff::class);

        Event::assertNotDispatched(JobFailed::class);
    }
}
