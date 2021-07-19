<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIp\RemoveUnassignedNicNats;
use App\Models\V2\Nat;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RemoveUnassignedNicNatsTest extends TestCase
{
    public function testJobCompletesWithNicAttached()
    {
        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        Event::fake([JobFailed::class, JobProcessed::class, ]);

        dispatch(new RemoveUnassignedNicNats($this->floatingIp()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testUnassignedNicTriggersNatDelete()
    {
        $nat = app()->make(Nat::class);
        $nat->destination()->associate($this->floatingIp());
        $nat->translated()->associate($this->nic());
        $nat->action = Nat::ACTION_DNAT;
        $nat->save();

        $nat = app()->make(Nat::class);
        $nat->source()->associate($this->nic());
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_SNAT;
        $nat->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new RemoveUnassignedNicNats($this->floatingIp()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_delete';
        });
    }
}
