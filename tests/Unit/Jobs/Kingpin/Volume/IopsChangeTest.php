<?php

namespace Tests\Unit\Jobs\Kingpin\Volume;

use App\Jobs\Kingpin\Volume\IopsChange;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
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
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                'iops' => 600
            ]);
        });

        $this->instanceModel()->volumes()->attach($volume);

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
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'bbff7e7b-c22e-4827-8d2c-a918087deefd',
                'iops' => 600
            ]);
        });

        $this->instanceModel()->volumes()->attach($volume);

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
            return Volume::factory()->create([
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
