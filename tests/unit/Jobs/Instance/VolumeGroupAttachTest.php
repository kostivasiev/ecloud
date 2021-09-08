<?php

namespace Tests\unit\Jobs\Instance;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\VolumeGroupAttach;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class VolumeGroupAttachTest extends TestCase
{
    use VolumeGroupMock, VolumeMock;

    /** @test */
    public function skipIfVolumeAlreadyMounted()
    {
        Log::partialMock()
            ->expects('info')
            ->withSomeOfArgs('Volume is already mounted on Instance, skipping')
            ->once();

        // add volume to volume group
        $this->volume()->volume_group_id = $this->volumeGroup()->id;
        $this->volume()->is_shared = true;
        $this->volume()->port = 0;
        $this->volume()->saveQuietly();

        // add volume group to instance and attach the volume
        $this->instance()->volume_group_id = $this->volumeGroup()->id;
        $this->instance()->volumes()->attach($this->volume());
        $this->instance()->saveQuietly();

        $this->assertEmpty((new VolumeGroupAttach($this->instance()))->handle());
    }

    /** @test */
    public function volumeAttachesSuccessfully()
    {
        // add volume to volume group
        $this->volume()->volume_group_id = $this->volumeGroup()->id;
        $this->volume()->is_shared = true;
        $this->volume()->port = 0;
        $this->volume()->saveQuietly();

        // add volume group to instance
        $this->instance()->volume_group_id = $this->volumeGroup()->id;
        $this->instance()->saveQuietly();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        // Assert volume is not currently attached
        $this->assertEquals(0, $this->instance()->volumes()->where('id', '=', $this->volume()->id)->count());

        dispatch(new VolumeGroupAttach($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
        Event::assertDispatched(Created::class);

        $this->instance()->refresh();

        // assert volume is now attached
        $this->assertEquals(1, $this->instance()->volumes()->where('id', '=', $this->volume()->id)->count());
    }
}
