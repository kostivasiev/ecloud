<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIp\RemoveNats;
use App\Models\V2\IpAddress;
use App\Models\V2\Nat;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RemoveNatsTest extends TestCase
{
    public function testJobCompletesWithNoNats()
    {
        $ipAddress = IpAddress::factory()->create();
        $this->floatingIp()->resource()->associate($ipAddress);
        $this->floatingIp()->save();

        Event::fake([JobFailed::class, JobProcessed::class, ]);

        dispatch(new RemoveNats($this->floatingIp()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testUnassignedIpAddressTriggersNatDelete()
    {
        $ipAddress = IpAddress::factory()->create();

        $destinationNat = app()->make(Nat::class);
        $destinationNat->destination()->associate($this->floatingIp());
        $destinationNat->translated()->associate($ipAddress);
        $destinationNat->action = Nat::ACTION_DNAT;
        $destinationNat->save();

        $sourceNat = app()->make(Nat::class);
        $sourceNat->source()->associate($ipAddress);
        $sourceNat->translated()->associate($this->floatingIp());
        $sourceNat->action = NAT::ACTION_SNAT;
        $sourceNat->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new RemoveNats($this->floatingIp()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class, function ($event) use ($destinationNat) {
            return $event->model->name == 'sync_delete'
                && $event->model->resource->id == $destinationNat->id;
        });

        Event::assertDispatched(Created::class, function ($event) use ($sourceNat) {
            return $event->model->name == 'sync_delete'
                && $event->model->resource->id == $sourceNat->id;
        });
    }
}
