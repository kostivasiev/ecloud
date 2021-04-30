<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Jobs\Instance\Undeploy\AwaitNicRemoval;
use App\Models\V2\Instance;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitNicRemovalTest extends TestCase
{
    use DatabaseMigrations;

    protected Instance $instance;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWithNoNics()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNicRemoval($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenNicSyncFailed()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
            $this->nic = $this->instance->nics()->create([
                'id' => 'vol-test',
                'mac_address' => 'aa:bb:cc:dd:ee:ff',
                'network_id' => 'net-test',
            ]);

            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'failure_reason' => 'test',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->nic);
            $task->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitNicRemoval($this->instance));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenNicSyncInProgress()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
            $this->nic = $this->instance->nics()->create([
                'id' => 'vol-test',
                'mac_address' => 'aa:bb:cc:dd:ee:ff',
                'network_id' => 'net-test',
            ]);

            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->nic);
            $task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNicRemoval($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
