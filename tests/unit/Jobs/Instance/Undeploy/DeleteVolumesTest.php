<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Jobs\FloatingIp\AwaitNatRemoval;
use App\Jobs\FloatingIp\AwaitNatSync;
use App\Jobs\FloatingIp\DeleteNats;
use App\Jobs\Instance\Undeploy\DeleteVolumes;
use App\Jobs\Nat\AwaitIPAddressAllocation;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Volume;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteVolumesTest extends TestCase
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

        dispatch(new DeleteVolumes($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testDeletesVolumeWithOnlyOneAttachment()
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
        });

        Event::fake();

        dispatch(new DeleteVolumes($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->volume->refresh();
        $this->assertNotNull($this->volume->deleted_at);
    }

    public function testDoesntDeleteVolumeWithMoreThanOneAttachment()
    {
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test1',
            ]);
            $instance2 = factory(Instance::class)->create([
                'id' => 'i-test2',
            ]);
            $this->volume = factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => 'vpc-test',
                'capacity' => 10,
            ]);
            $this->instance->volumes()->attach($this->volume);
            $instance2->volumes()->attach($this->volume);
        });

        Event::fake();

        dispatch(new DeleteVolumes($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->volume->refresh();
        $this->assertNull($this->volume->deleted_at);
    }
}
