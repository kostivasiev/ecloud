<?php

namespace Tests\Unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\AwaitNatRemoval;
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

class AwaitNatRemovalTest extends TestCase
{
    protected Nat $nat;
    protected FloatingIp $floatingIp;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobCompletesWithNicAttached()
    {
        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNatRemoval($this->floatingIp()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenSourceNatSyncFailed()
    {
        Model::withoutEvents(function () {
            $this->floatingIp = FloatingIp::factory()->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = Nic::factory()->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
            ]);
            $this->nic->ip_address = '10.3.4.5';

            $nat = app()->make(Nat::class);
            $nat->id = 'nat-test';
            $nat->source()->associate($this->nic);
            $nat->translated()->associate($this->floatingIp);
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
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitNatRemoval($this->floatingIp));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobFailedWhenDestinationNatSyncFailed()
    {
        Model::withoutEvents(function () {
            $this->floatingIp = FloatingIp::factory()->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = Nic::factory()->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
            ]);

            $this->nic->ip_address = '10.3.4.5';

            $nat = app()->make(Nat::class);
            $nat->id = 'nat-test';
            $nat->destination()->associate($this->floatingIp);
            $nat->translated()->associate($this->nic);
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
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitNatRemoval($this->floatingIp));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenSourceNatExistsAndSyncInProgress()
    {
        Model::withoutEvents(function () {
            $this->floatingIp = FloatingIp::factory()->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = Nic::factory()->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
            ]);
            $this->nic->ip_address = '10.3.4.5';

            $nat = app()->make(Nat::class);
            $nat->id = 'nat-test';
            $nat->source()->associate($this->nic);
            $nat->translated()->associate($this->floatingIp);
            $nat->action = NAT::ACTION_SNAT;
            $nat->save();

            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($nat);
            $task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNatRemoval($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testJobReleasedWhenDestinationNatExistsAndSyncInProgress()
    {
        Model::withoutEvents(function () {
            $this->floatingIp = FloatingIp::factory()->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = Nic::factory()->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
            ]);
            $this->nic->ip_address = '10.3.4.5';
            $nat = app()->make(Nat::class);
            $nat->id = 'nat-test';
            $nat->destination()->associate($this->floatingIp);
            $nat->translated()->associate($this->nic);
            $nat->action = NAT::ACTION_SNAT;
            $nat->save();

            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($nat);
            $task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNatRemoval($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
