<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitNatSyncTest extends TestCase
{
    protected Nat $nat;
    protected FloatingIp $floatingIp;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWithNoNats()
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

        dispatch(new AwaitNatSync($this->floatingIp));

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

            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
                'failure_reason' => 'test',
            ]);
            $sync->resource()->associate($nat);
            $sync->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitNatSync($this->floatingIp));

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

            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
                'failure_reason' => 'test',
            ]);
            $sync->resource()->associate($nat);
            $sync->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitNatSync($this->floatingIp));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenSourceNatExistsAndSyncInProgress()
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

            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
            ]);
            $sync->resource()->associate($nat);
            $sync->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNatSync($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testJobReleasedWhenDestinationNatExistsAndSyncInProgress()
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
            $nat->action = NAT::ACTION_SNAT;
            $nat->save();

            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
            ]);
            $sync->resource()->associate($nat);
            $sync->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNatSync($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
