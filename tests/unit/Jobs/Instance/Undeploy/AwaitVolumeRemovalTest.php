<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\Instance\Undeploy\AwaitVolumeRemoval;
use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitVolumeRemovalTest extends TestCase
{
    use DatabaseMigrations;

    protected Instance $instance;
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWithNoVolumes()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitVolumeRemoval($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenVolumeSyncFailed()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
            $this->volume = factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => 'vpc-test',
                'capacity' => 10,
            ]);
            $this->instance->volumes()->attach($this->volume);

            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
                'failure_reason' => 'test',
            ]);
            $sync->resource()->associate($this->volume);
            $sync->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitVolumeRemoval($this->instance));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenVolumeSyncInProgress()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
            $this->volume = factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => 'vpc-test',
                'capacity' => 10,
            ]);
            $this->instance->volumes()->attach($this->volume);

            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
            ]);
            $sync->resource()->associate($this->volume);
            $sync->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitVolumeRemoval($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
