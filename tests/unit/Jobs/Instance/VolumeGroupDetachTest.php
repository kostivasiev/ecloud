<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\VolumeGroupDetach;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;

class VolumeGroupDetachTest extends TestCase
{
    use VolumeGroupMock, VolumeMock;

    /** @test */
    public function skipIfVolumeGroupIdIsNotEmpty()
    {
        Log::partialMock()
            ->expects('info')
            ->withSomeOfArgs('No volumes to unmount from Instance, skipping')
            ->once();

        // add volume group to instance and attach the volume
        $this->instance()->volume_group_id = $this->volumeGroup()->id;
        $this->instance()->volumes()->attach($this->volume());
        $this->instance()->saveQuietly();

        $this->assertEmpty((new VolumeGroupDetach($this->instance()))->handle());
    }

    /** @test */
    public function volumeDetachesSuccessfully()
    {
        $this->volume()->is_shared = true;
        $this->volume()->port = 0;
        $this->volume()->saveQuietly();
        $this->instance()->volumes()->attach($this->volume());
        $this->instance()->saveQuietly();

        // kingpin mocks
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['volumes' => []]));
            });

        (new VolumeGroupDetach($this->instance()))->handle();

        $this->assertEquals(0, $this->instance()->volumes()->count());
    }
}
