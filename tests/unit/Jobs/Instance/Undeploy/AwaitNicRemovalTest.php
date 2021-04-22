<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\Instance\Undeploy\AwaitNicRemoval;
use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitNicRemovalTest extends TestCase
{
    use DatabaseMigrations;

    protected Instance $instance;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWithNoNics()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNicRemoval($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenNicSyncFailed()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
            $this->nic = $this->instance->nics()->create([
                'id' => 'vol-test',
                'mac_address' => 'aa:bb:cc:dd:ee:ff',
                'network_id' => 'net-test',
            ]);

            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
                'failure_reason' => 'test',
            ]);
            $sync->resource()->associate($this->nic);
            $sync->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitNicRemoval($this->instance));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenNicSyncInProgress()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
            $this->nic = $this->instance->nics()->create([
                'id' => 'vol-test',
                'mac_address' => 'aa:bb:cc:dd:ee:ff',
                'network_id' => 'net-test',
            ]);

            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
            ]);
            $sync->resource()->associate($this->nic);
            $sync->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitNicRemoval($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
