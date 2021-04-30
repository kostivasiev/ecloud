<?php

namespace Tests\unit\Jobs\Tasks;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Jobs\Tasks\AwaitTaskJob;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Support\Sync;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class TestModel extends Model
{
    use Taskable;

    protected $fillable = [
        'id',
    ];
}

class AwaitTaskJobTest extends TestCase
{
    use DatabaseMigrations;

    protected Task $task;
    protected $model;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWithCompletedTask()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-test',
                'name' => 'test',
                'completed' => true,
            ]);

            $this->task->resource()->associate($this->instance());
            $this->task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitTaskJob($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWithFailedTask()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-test',
                'name' => 'test',
                'failure_reason' => 'test',
            ]);

            $this->task->resource()->associate($this->instance());
            $this->task->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitTaskJob($this->task));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenTaskInProgress()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-test',
                'name' => 'test',
            ]);

            $this->task->resource()->associate($this->instance());
            $this->task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitTaskJob($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
