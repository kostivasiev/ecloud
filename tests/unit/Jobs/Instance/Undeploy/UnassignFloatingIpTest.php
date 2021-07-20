<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Undeploy\UnassignFloatingIP;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UnassignFloatingIpTest extends TestCase
{
    public function testJobCompletesAssignedFloatingIps()
    {
        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new UnassignFloatingIP($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'floating_ip_unassign'
                && $event->model->resource->id == $this->floatingIp()->id;
        });
    }

    public function testJobCompletesNoAssignedFloatingIps()
    {
        $this->nic();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new UnassignFloatingIP($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
