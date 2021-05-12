<?php

namespace Tests\unit\Jobs\Kingpin\Volume;

use App\Events\V2\Nic\Saved;
use App\Events\V2\Nic\Saving;
use App\Jobs\Instance\Deploy\ConfigureNics;
use App\Jobs\Kingpin\Volume\Deploy;
use App\Jobs\Kingpin\Volume\Undeploy;
use App\Models\V2\Nic;
use App\Models\V2\Volume;
use App\Rules\V2\IpAvailable;
use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\QueryException;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testDeploysWhenNoVMwareUuidDefined()
    {
        $volume = Volume::withoutEvents(function() {
            return factory(Volume::class)->create([
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
            $volume = factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
            ]);

            $this->instance()->volumes()->attach($volume);
            return $volume;
        });

        Event::fake();

        dispatch(new Deploy($volume));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFailsWhenKingpinMissingUuidInResponse()
    {
        $volume = Volume::withoutEvents(function() {
            return factory(Volume::class)->create([
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
            return factory(Volume::class)->create([
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
}
