<?php

namespace Tests\unit\Jobs\Kingpin\Volume;

use App\Events\V2\Nic\Saved;
use App\Events\V2\Nic\Saving;
use App\Jobs\Instance\Deploy\ConfigureNics;
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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class IopsChangeTest extends TestCase
{
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testIopsUpdatedWhenDifferent()
    {
        $volume = Volume::withoutEvents(function() {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                'iops' => 600
            ]);
        });

        $this->instance()->volumes()->attach($volume);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'iops' => '300',
                ]));
            });

        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd/iops',
                [
                    "json" => [
                        "limit" => 600,
                    ],
                ],
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                ]));
            });

        Event::fake();

        dispatch(new IopsChange($volume));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testIopsNotUpdatedWhenSame()
    {
        $volume = Volume::withoutEvents(function() {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                'iops' => 600
            ]);
        });

        $this->instance()->volumes()->attach($volume);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/volume/bbff7e7b-c22e-4827-8d2c-a918087deefd',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'iops' => '600',
                ]));
            });

        Event::fake();

        dispatch(new IopsChange($volume));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testIopsNotUpdatedWhenNotAttachedToInstance()
    {
        $volume = Volume::withoutEvents(function() {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                'iops' => 600
            ]);
        });

        Event::fake();

        dispatch(new IopsChange($volume));

        Event::assertNotDispatched(JobFailed::class);
    }
}
