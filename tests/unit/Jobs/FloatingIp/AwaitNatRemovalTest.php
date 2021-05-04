<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitNatRemovalTest extends TestCase
{
    use DatabaseMigrations;

    protected Nat $nat;
    protected FloatingIp $floatingIp;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWhenNoNatsExist()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
                'ip_address' => '10.3.4.5',
            ]);
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNatRemoval($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenSourceNatSyncFailed()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
                'ip_address' => '10.3.4.5',
            ]);

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
                'name' => Sync::TASK_NAME_DELETE,
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
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
                'ip_address' => '10.3.4.5',
            ]);

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
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $task->resource()->associate($nat);
            $task->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitNatRemoval($this->floatingIp));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenSourceNatExists()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
                'ip_address' => '10.3.4.5',
            ]);
            $this->nat = app()->make(Nat::class);
            $this->nat->id = 'nat-test';
            $this->nat->source()->associate($this->nic);
            $this->nat->translated()->associate($this->floatingIp);
            $this->nat->action = NAT::ACTION_SNAT;
            $this->nat->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNatRemoval($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testJobReleasedWhenDestinationNatExists()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
                'ip_address' => '10.3.4.5',
            ]);
            $this->nat = app()->make(Nat::class);
            $this->nat->id = 'nat-test';
            $this->nat->destination()->associate($this->floatingIp);
            $this->nat->translated()->associate($this->nic);
            $this->nat->action = NAT::ACTION_DNAT;
            $this->nat->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNatRemoval($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
