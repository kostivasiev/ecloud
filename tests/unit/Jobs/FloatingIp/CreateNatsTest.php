<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIp\CreateNats;
use App\Models\V2\IpAddress;
use App\Models\V2\Nat;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateNatsTest extends TestCase
{
    public function testIpAddressAttachedCreatesNats()
    {
        $ipAddress = IpAddress::factory()->create();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateNats($this->floatingIp(), $ipAddress));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update'
                && $event->model->resource_type == Nat::class
                && $event->model->resource->action == Nat::ACTION_DNAT;
        });

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update'
                && $event->model->resource_type == Nat::class
                && $event->model->resource->action == NAT::ACTION_SNAT;
        });
    }

    public function testOtherResourceAssignedCompletesWithoutNats()
    {
        $this->floatingIp()->resource()->associate($this->instanceModel());
        $this->floatingIp()->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateNats($this->floatingIp(), $this->router()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class);
    }
}
