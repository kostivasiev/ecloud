<?php

namespace Tests\unit\Jobs\Nat;

use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitIPAdressAllocationTest extends TestCase
{
    use DatabaseMigrations;

    protected Nat $nat;
    protected FloatingIp $floatingIp;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWithValidIPAddresses()
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
            $this->nat->action = NAT::ACTION_SNAT;
            $this->nat->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitIPAddressAllocation($this->nat));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobReleasedWhenNoSourceIPAddress()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
                'ip_address' => '',
            ]);
            $this->nat = app()->make(Nat::class);
            $this->nat->id = 'nat-test';
            $this->nat->source()->associate($this->nic);
            $this->nat->translated()->associate($this->floatingIp);
            $this->nat->action = NAT::ACTION_SNAT;
            $this->nat->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitIPAddressAllocation($this->nat));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testJobReleasedWhenNoDestinationIPAddress()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '',
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

        dispatch(new AwaitIPAddressAllocation($this->nat));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testJobReleasedWhenNoTranslatedIPAddress()
    {
        Model::withoutEvents(function() {
            $this->floatingIp = factory(FloatingIp::class)->create([
                'id' => 'fip-test',
                'ip_address' => '10.2.3.4',
            ]);
            $this->nic = factory(Nic::class)->create([
                'id' => 'nic-test',
                'network_id' => $this->network()->id,
                'ip_address' => '',
            ]);
            $this->nat = app()->make(Nat::class);
            $this->nat->id = 'nat-test';
            $this->nat->destination()->associate($this->floatingIp);
            $this->nat->translated()->associate($this->nic);
            $this->nat->action = NAT::ACTION_SNAT;
            $this->nat->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitIPAddressAllocation($this->nat));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
