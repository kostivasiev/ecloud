<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\FloatingIp\DeleteNats;
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

        Event::fake([JobFailed::class]);

        dispatch(new DeleteNats($this->floatingIp));

        Event::assertNotDispatched(JobFailed::class);

        $this->nat->refresh();
        $this->assertNotNull($this->nat->deleted_at);
    }
}
