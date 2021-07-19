<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIp\DeleteNats;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteNatsTest extends TestCase
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
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteNats($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testSourceNatDeletedWhenExists()
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

        dispatch(new DeleteNats($this->floatingIp()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_delete';
        });
    }
}
