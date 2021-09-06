<?php

namespace Tests\unit\Jobs\Kingpin\Instance;

use App\Jobs\Kingpin\Instance\AttachVolume;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AttachVolumeTest extends TestCase
{
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testVolumeAttaches()
    {
        /** @var Volume $volume */
        $volume = Volume::factory()->createOne([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            'capacity' => 30
        ]);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'volumes' => []
                ]));
            });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/attach',
                [
                    'json' => [
                        'volumeUUID' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'volumes' => []
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new AttachVolume($this->instance(), $volume));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(1, $this->instance()->volumes()->count());
    }

    public function testVolumeAlreadyAttachedSkips()
    {
        /** @var Volume $volume */
        $volume = Volume::factory()->createOne([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            'capacity' => 30
        ]);

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

        Event::fake([JobFailed::class]);

        dispatch(new AttachVolume($this->instance(), $volume));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(1, $this->instance()->volumes()->count());
    }

    public function testRetrieveInstanceInvalidJsonThrowsException()
    {
        /** @var Volume $volume */
        $volume = Volume::factory()->createOne([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            'capacity' => 30
        ]);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], 'invalid');
            });

        $this->expectExceptionMessage('Failed to retrieve instance i-test from Kingpin, invalid JSON');

        dispatch(new AttachVolume($this->instance(), $volume));
    }

    public function testMaximumVolumeAttachmentReachedFails()
    {
        Config::set('volume.instance.limit', 1);

        $volumes = Volume::factory()
            ->count(2)
            ->state(new Sequence(
                ['id' => 'vol-test-' . uniqid(), 'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd'],
                ['id' => 'vol-test-' . uniqid(), 'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefe'],
            ))
            ->create([
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'capacity' => 30
            ]);

        $this->instance()->volumes()->attach($volumes[0]);

        Event::fake([JobFailed::class]);

        dispatch(new AttachVolume($this->instance(), $volumes[1]));

        Event::assertDispatched(JobFailed::class, function ($event) use ($volumes) {
            return $event->exception->getMessage() == 'Failed to attach volume ' . $volumes[1]->id .'  to instance i-test, volume limit exceeded';
        });
    }
}
