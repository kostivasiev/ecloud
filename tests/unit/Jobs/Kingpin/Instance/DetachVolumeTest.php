<?php

namespace Tests\unit\Jobs\Kingpin\Instance;

use App\Jobs\Kingpin\Instance\AttachVolume;
use App\Jobs\Kingpin\Instance\DetachVolume;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DetachVolumeTest extends TestCase
{
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testVolumeDetaches()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            'capacity' => 30
        ]);

        $this->instanceModel()->volumes()->attach($volume);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'volumes' => [
                        [
                            'uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                        ]
                    ]
                ]));
            });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd/detach',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DetachVolume($this->instanceModel(), $volume));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(0, $this->instanceModel()->volumes()->count());
    }

    public function testVolumeAlreadyDetachedSkips()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            'capacity' => 30
        ]);

        $this->instanceModel()->volumes()->attach($volume);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'volumes' => []
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DetachVolume($this->instanceModel(), $volume));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(0, $this->instanceModel()->volumes()->count());
    }

    public function testRetrieveInstanceInvalidJsonThrowsException()
    {
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            'capacity' => 30
        ]);

        $this->instanceModel()->volumes()->attach($volume);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], 'invalid');
            });

        $this->expectExceptionMessage('Failed to retrieve instance i-test from Kingpin, invalid JSON');

        dispatch(new DetachVolume($this->instanceModel(), $volume));
    }
}
