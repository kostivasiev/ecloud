<?php

namespace Tests\unit\Listeners\Volume;

use App\Listeners\V2\Volume\CapacityIncrease;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Region;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class BillingMetricTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $region;
    protected $availabilityZone;
    protected $vpc;
    protected $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        Model::withoutEvents(function () {
            $this->volume = factory(Volume::class)->create([
                'id' => 'vol-aaaaaaaa',
                'vpc_id' => $this->vpc->getKey(),
                'capacity' => 10,
                'availability_zone_id' => $this->availabilityZone->getKey()
            ]);
        });
        $this->volume->vpc()->associate($this->vpc);

        $mockKingpinService = \Mockery::mock(new KingpinService(new Client()));
        $mockKingpinService->shouldReceive('put')->andReturn(
            new Response(200)
        );

        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });
    }

    public function testResizingVolumeAddsBillingMetric()
    {
        $this->volume->capacity = 15;
        $this->volume->save();

        Event::assertDispatched(\App\Events\V2\Volume\Saving::class, function ($event) {
            return $event->model->id === $this->volume->id;
        });

        Event::assertDispatched(\App\Events\V2\Volume\Saved::class, function ($event) {
            return $event->model->id === $this->volume->id;
        });

        $resourceSyncListener = \Mockery::mock(\App\Listeners\V2\ResourceSync::class)->makePartial();
        $resourceSyncListener->handle(new \App\Events\V2\Volume\Saving($this->volume));

        $sync = Sync::where('resource_id', $this->volume->id)->first();

        $capacityIncreaseListener = \Mockery::mock(CapacityIncrease::class)->makePartial();
        $capacityIncreaseListener->handle(new \App\Events\V2\Volume\Saved($this->volume));

        // sync set to complete by the CapacityIncrease listener
        Event::assertDispatched(\App\Events\V2\Sync\Updated::class, function ($event) use ($sync) {
            return $event->model->id === $sync->id;
        });

        $sync->refresh();

        // Check that the volume billing metric is added
        $dispatchResourceSyncedEventListener = \Mockery::mock(\App\Listeners\V2\Volume\UpdateBilling::class)->makePartial();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $this->assertEquals(1, BillingMetric::where('resource_id', $this->volume->getKey())->count());

        $metric = BillingMetric::where('resource_id', $this->volume->getKey())->first();

        $this->assertNotNull($metric);
        $this->assertStringStartsWith('disk.capacity', $metric->key);
    }

    public function testResizingVolumeEndsExistingBillingMetric()
    {
        $metric = factory(BillingMetric::class)->create([
            'resource_id' => 'vol-aaaaaaaa',
            'vpc_id' => $this->vpc->getKey(),
            'key' => 'disk.capacity',
            'value' => 10,
            'start' => '2020-07-07T10:30:00+01:00',
        ]);

        $this->assertEquals(1, BillingMetric::where('resource_id', $this->volume->getKey())->count());
        $this->assertNull($metric->end);

        $this->volume->capacity = 15;
        $this->volume->save();

        $resourceSyncListener = \Mockery::mock(\App\Listeners\V2\ResourceSync::class)->makePartial();
        $resourceSyncListener->handle(new \App\Events\V2\Volume\Saving($this->volume));

        $sync = Sync::where('resource_id', $this->volume->id)->first();

        $capacityIncreaseListener = \Mockery::mock(CapacityIncrease::class)->makePartial();
        $capacityIncreaseListener->handle(new \App\Events\V2\Volume\Saved($this->volume));

        $sync->refresh();

        $dispatchResourceSyncedEventListener = \Mockery::mock(\App\Listeners\V2\Volume\UpdateBilling::class)->makePartial();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Sync\Updated($sync));

        $this->assertEquals(2, BillingMetric::where('resource_id', $this->volume->getKey())->count());

        $metric->refresh();

        $this->assertNotNull($metric->end);
    }
}
