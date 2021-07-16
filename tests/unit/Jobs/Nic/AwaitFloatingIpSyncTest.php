<?php

namespace Tests\unit\Jobs\Nic;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\Nic\AwaitFloatingIpSync;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitFloatingIpSyncTest extends TestCase
{
    protected Nat $nat;
    protected FloatingIp $floatingIp;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWhenNoFloatingIpExist()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFloatingIpSync($this->nic()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenFloatingIpSyncFailed()
    {
        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'failure_reason' => 'test',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->floatingIp());
            $task->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitFloatingIpSync($this->nic()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenFloatingIpSyncing()
    {
        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'failure_reason' => null,
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($this->floatingIp());
            $task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFloatingIpSync($this->nic()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
