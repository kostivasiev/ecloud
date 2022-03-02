<?php

namespace Tests\unit\Jobs\Kingpin\Volume;

use App\Jobs\Kingpin\Volume\Deploy;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeployTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testDeploysWhenNoVMwareUuidDefined()
    {
        $volume = Volume::withoutEvents(function() {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/volume',
                [
                    "json" => [
                        "volumeId" => "vol-test",
                        "sizeGiB" => "100",
                        "shared" => false,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                ]));
            });

        Event::fake();

        dispatch(new Deploy($volume));

        Event::assertNotDispatched(JobFailed::class);

        $volume->refresh();
        $this->assertEquals('bbff7e7b-c22e-4827-8d2c-a918087deefd', $volume->vmware_uuid);
    }

    public function testVolumeNotDeployedWhenVMwareUuidDefined()
    {
        $volume = Volume::withoutEvents(function() {
            $volume = Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            ]);

            $this->instanceModel()->volumes()->attach($volume);
            return $volume;
        });

        Event::fake();

        dispatch(new Deploy($volume));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFailsWhenKingpinMissingUuidInResponse()
    {
        $volume = Volume::withoutEvents(function() {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/volume',
                [
                    "json" => [
                        "volumeId" => "vol-test",
                        "sizeGiB" => "100",
                        "shared" => false,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, []);
            });

        Event::fake();

        $this->expectException(\Exception::class);
        dispatch(new Deploy($volume));

        Event::assertDispatched(JobFailed::class);
    }

    public function testFailsWhenKingpinUuidEmptyInResponse()
    {
        $volume = Volume::withoutEvents(function() {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/volume',
                [
                    "json" => [
                        "volumeId" => "vol-test",
                        "sizeGiB" => "100",
                        "shared" => false,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => '',
                ]));
            });

        Event::fake();

        $this->expectException(\Exception::class);
        dispatch(new Deploy($volume));

        Event::assertDispatched(JobFailed::class);
    }

    public function testDeploySharedVolume()
    {
        $volume = Volume::withoutEvents(function() {
            return Volume::factory()->sharedVolume()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/volume',
                [
                    "json" => [
                        "volumeId" => "vol-test",
                        "sizeGiB" => "100",
                        "shared" => true,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                ]));
            });

        Event::fake();

        dispatch(new Deploy($volume));

        Event::assertNotDispatched(JobFailed::class);

        $volume->refresh();
        $this->assertEquals('bbff7e7b-c22e-4827-8d2c-a918087deefd', $volume->vmware_uuid);
    }
}
