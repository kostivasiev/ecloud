<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Undeploy\DeleteVolumes;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteVolumesTest extends TestCase
{
    protected Instance $instance;
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWithNoVolumes()
    {
        Model::withoutEvents(function() {
            $this->instance = Instance::factory()->create([
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

    public function testDeletesOSVolume()
    {
        Model::withoutEvents(function() {
            $this->instance = Instance::factory()->create([
                'id' => 'i-test',
            ]);
            $this->volume = Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'capacity' => 10,
                'os_volume' => true,
            ]);
            $this->instance->volumes()->attach($this->volume);
        });

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new DeleteVolumes($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testDetachesDataVolume()
    {
        Model::withoutEvents(function() {
            $this->instance = Instance::factory()->create([
                'id' => 'i-test1',
            ]);
            $this->volume = Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => 'vpc-test',
                'capacity' => 10,
                'os_volume' => false,
            ]);
            $this->instance->volumes()->attach($this->volume);
        });

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new DeleteVolumes($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
