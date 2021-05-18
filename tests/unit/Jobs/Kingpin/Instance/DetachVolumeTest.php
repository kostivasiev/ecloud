<?php

namespace Tests\unit\Jobs\Kingpin\Volume;

use App\Events\V2\Nic\Saved;
use App\Events\V2\Nic\Saving;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Jobs\Kingpin\Instance\AttachVolume;
use App\Jobs\Kingpin\Volume\CapacityChange;
use App\Jobs\Kingpin\Volume\Deploy;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Jobs\Kingpin\Volume\Undeploy;
use App\Models\V2\Nic;
use App\Models\V2\Volume;
use App\Rules\V2\IpAvailable;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\QueryException;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AttachVolumeTest extends TestCase
{
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testVolumeAttaches()
    {
        $volume = factory(Volume::class)->create([
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
    }

    public function testVolumeAlreadyAttachedSkips()
    {
        $volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            'capacity' => 30
        ]);

        $this->instance()->volumes()->attach($volume);

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
    }

    public function testRetrieveInstanceInvalidJsonThrowsException()
    {
        $volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            'capacity' => 30
        ]);

        $this->instance()->volumes()->attach($volume);

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

        $volume1 = factory(Volume::class)->create([
            'id' => 'vol-test1',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            'capacity' => 30
        ]);

        $volume2 = factory(Volume::class)->create([
            'id' => 'vol-test2',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefe',
            'capacity' => 30
        ]);

        $this->instance()->volumes()->attach($volume1);

        Event::fake([JobFailed::class]);

        dispatch(new AttachVolume($this->instance(), $volume2));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to attach volume vol-test2  to instance i-test, volume limit exceeded';
        });
    }
}
