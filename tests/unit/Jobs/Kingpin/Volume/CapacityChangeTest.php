<?php

namespace Tests\unit\Jobs\Kingpin\Volume;

use App\Jobs\Kingpin\Volume\CapacityChange;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CapacityChangeTest extends TestCase
{
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCapacityIncreasedWhenConnectedToInstanceAndDifferent()
    {
        $volume = Volume::withoutEvents(function() {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                'capacity' => 30
            ]);
        });

        $this->instanceModel()->volumes()->attach($volume);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'sizeGiB' => '20',
                ]));
            });

        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd/size',
                [
                    "json" => [
                        "sizeGiB" => 30,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake();

        dispatch(new CapacityChange($volume));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCapacityNotChangedWhenConnectedToInstanceAndNotDifferent()
    {
        $volume = Volume::withoutEvents(function() {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                'capacity' => 30
            ]);
        });

        $this->instanceModel()->volumes()->attach($volume);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'sizeGiB' => '30',
                ]));
            });

        Event::fake();

        dispatch(new CapacityChange($volume));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCapacityIncreasedWhenNotConnectedToInstanceAndDifferent()
    {
        $volume = Volume::withoutEvents(function() {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                'capacity' => 30
            ]);
        });

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'sizeGiB' => '20',
                ]));
            });

        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd/size',
                [
                    "json" => [
                        "sizeGiB" => 30,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake();

        dispatch(new CapacityChange($volume));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCapacityNotChangedWhenNotConnectedToInstanceAndNotDifferent()
    {
        $volume = Volume::withoutEvents(function() {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                'capacity' => 30
            ]);
        });

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'sizeGiB' => '30',
                ]));
            });

        Event::fake();

        dispatch(new CapacityChange($volume));

        Event::assertNotDispatched(JobFailed::class);
    }
}
