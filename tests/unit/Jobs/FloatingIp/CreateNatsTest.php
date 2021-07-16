<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIp\CreateNats;
use App\Models\V2\Nat;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateNatsTest extends TestCase
{

    public function testNicAttachedCreatesNats()
    {
        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateNats($this->floatingIp()));

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
        $this->floatingIp()->resource()->associate($this->instance());
        $this->floatingIp()->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateNats($this->floatingIp()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class);
    }

    public function testNoAttachedResourceCompletes()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateNats($this->floatingIp()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class);
    }
}
