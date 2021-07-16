<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\FloatingIp\AwaitUnassignedNicNatRemoval;
use App\Jobs\FloatingIp\RemoveUnassignedNicNats;
use App\Jobs\Nic\AwaitFloatingIpUnassign;
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

class AwaitFloatingIpUnassignTest extends TestCase
{
    protected Nat $nat;
    protected FloatingIp $floatingIp;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobFailedWhenSourceNatSyncFailed()
    {
        $nat = app()->make(Nat::class);
        $nat->source()->associate($this->nic());
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_SNAT;
        $nat->save();

        $task = new Task([
            'id' => 'task-1',
            'completed' => false,
            'failure_reason' => 'test',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $task->resource()->associate($nat);
        $task->save();

        Event::fake([JobFailed::class]);

        dispatch(new AwaitFloatingIpUnassign($this->nic()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobFailedWhenDestinationNatSyncFailed()
    {
        $nat = app()->make(Nat::class);
        $nat->source()->associate($this->nic());
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_DNAT;
        $nat->save();

        $task = new Task([
            'id' => 'task-1',
            'completed' => false,
            'failure_reason' => 'test',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $task->resource()->associate($nat);
        $task->save();

        Event::fake([JobFailed::class]);

        dispatch(new AwaitFloatingIpUnassign($this->nic()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenSourceNatExistsAndSyncInProgress()
    {
        $nat = app()->make(Nat::class);
        $nat->source()->associate($this->nic());
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_SNAT;
        $nat->save();

        $task = new Task([
            'id' => 'task-1',
            'completed' => false,
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $task->resource()->associate($nat);
        $task->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFloatingIpUnassign($this->nic()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testJobReleasedWhenDestinationNatExistsAndSyncInProgress()
    {
        $nat = app()->make(Nat::class);
        $nat->source()->associate($this->nic());
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_DNAT;
        $nat->save();

        $task = new Task([
            'id' => 'task-1',
            'completed' => false,
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $task->resource()->associate($nat);
        $task->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFloatingIpUnassign($this->nic()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
