<?php

namespace Tests\unit\Listeners\Volume;

use App\Models\V2\BillingMetric;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class BillingMetricTest extends TestCase
{
    use DatabaseMigrations;

    protected $volume;

    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume',
                [
                    'json' => [
                        'volumeId' => 'vol-test',
                        'sizeGiB' => '10',
                        'shared' => false,
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => 'uuid-test-uuid-test-uuid-test']));
            });

        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume/uuid-test-uuid-test-uuid-test/size',
                [
                    'json' => [
                        'sizeGiB' => '10',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->volume = factory(Volume::class)->create([
            'id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'capacity' => 10,
            'iops' => 300,
            'availability_zone_id' => $this->availabilityZone()->id
        ]);
    }

    public function testResizingVolumeAddsBillingMetric()
    {
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume/uuid-test-uuid-test-uuid-test/size',
                [
                    'json' => [
                        'sizeGiB' => '15',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->volume->capacity = 15;
        $this->volume->save();

        $sync = Sync::where('resource_id', $this->volume->id)->first();

        // Check that the volume billing metric is added
        $dispatchResourceSyncedEventListener = \Mockery::mock(\App\Listeners\V2\Volume\UpdateBilling::class)->makePartial();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $this->assertEquals(1, BillingMetric::where('resource_id', $this->volume->id)->count());

        $metric = BillingMetric::where('resource_id', $this->volume->id)->first();

        $this->assertNotNull($metric);
        $this->assertStringStartsWith('disk.capacity', $metric->key);
    }

    public function testResizingVolumeEndsExistingBillingMetric()
    {
        $this->kingpinServiceMock()->expects('put')
            ->withArgs([
                '/api/v1/vpc/vpc-test/volume/uuid-test-uuid-test-uuid-test/size',
                [
                    'json' => [
                        'sizeGiB' => '15',
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $metric = factory(BillingMetric::class)->create([
            'resource_id' => 'vol-test',
            'vpc_id' => $this->vpc()->id,
            'key' => 'disk.capacity.300',
            'value' => 10,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->assertEquals(1, BillingMetric::where('resource_id', $this->volume->id)->count());
        $this->assertNull($metric->end);

        $this->volume->capacity = 15;
        $this->volume->iops = 600;
        $this->volume->save();

        $resourceSyncListener = \Mockery::mock(\App\Listeners\V2\ResourceSync::class)->makePartial();
        $resourceSyncListener->handle(new \App\Events\V2\Volume\Saving($this->volume));

        $sync = Sync::where('resource_id', $this->volume->id)->first();

        $sync->refresh();

        $dispatchResourceSyncedEventListener = \Mockery::mock(\App\Listeners\V2\Volume\UpdateBilling::class)->makePartial();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $this->assertEquals(2, BillingMetric::where('resource_id', $this->volume->id)->count());

        $metric->refresh();

        $this->assertNotNull($metric->end);
    }
}
