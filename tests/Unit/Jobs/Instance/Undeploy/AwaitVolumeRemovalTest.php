<?php

namespace Tests\Unit\Jobs\Instance\Undeploy;

use App\Jobs\Instance\Undeploy\AwaitVolumeRemoval;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitVolumeRemovalTest extends TestCase
{
    protected Instance $instance;
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWithNoVolumes()
    {
        Model::withoutEvents(function() {
            $this->instance = Instance::factory()->create([
                'id' => 'i-test',
            ]);
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitVolumeRemoval($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenVolumeSyncFailed()
    {
        Model::withoutEvents(function() {
            $this->instance = Instance::factory()->create([
                'id' => 'i-test',
            ]);
            $this->volume = $this->instance->volumes()->create([
                'id' => 'vol-test',
                'vpc_id' => 'vpc-test',
                'capacity' => 10,
                'os_volume' => true,
            ]);

            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'failure_reason' => 'test',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->volume);
            $task->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitVolumeRemoval($this->instance));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenNicSyncInProgress()
    {
        Model::withoutEvents(function() {
            $this->instance = Instance::factory()->create([
                'id' => 'i-test',
            ]);
            $this->volume = $this->instance->volumes()->create([
                'id' => 'vol-test',
                'vpc_id' => 'vpc-test',
                'capacity' => 10,
                'os_volume' => true,
            ]);

            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->volume);
            $task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitVolumeRemoval($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
