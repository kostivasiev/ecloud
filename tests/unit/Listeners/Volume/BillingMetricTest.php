<?php

namespace Tests\unit\Listeners\Volume;

use App\Events\V2\Volume\Updated;
use App\Jobs\AvailabilityZoneCapacity\UpdateFloatingIpCapacity;
use App\Listeners\V2\Volume\CapacityIncrease;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use App\Services\V2\NsxService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
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
                'id' => 'v-aaaaaaaa',
                'vpc_id' => $this->vpc->getKey(),
                'capacity' => 10,
                'availability_zone_id' => $this->availabilityZone->getKey()
            ]);
        });
        $this->volume->vpc()->associate($this->vpc);
        //$this->volume->instances()->attach($instance);

        $mockKingpinService = \Mockery::mock(new KingpinService(new Client()));
        $mockKingpinService->shouldReceive('put')->andReturn(
            new Response(200)
        );

        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });

    }



    public function testresizingVolumeUpdatesBilling()
    {
        Queue::Fake();

        $this->volume->capacity = 15;
        $this->volume->save();

        $resourceSyncListener = \Mockery::mock(\App\Listeners\V2\ResourceSync::class)->makePartial();

        $resourceSyncListener->handle(new \App\Events\V2\Volume\Saving($this->volume));

        Event::assertDispatched(\App\Events\V2\Volume\Saving::class, function ($event) {
            return $event->model->id === $this->volume->id;
        });

        Event::assertDispatched(\App\Events\V2\Volume\Updated::class, function ($event) {
            return $event->volume->id === $this->volume->id;
        });

        $sync = Sync::where('resource_id', $this->volume->id)->first();

        $capacityIncreaseListener = \Mockery::mock(CapacityIncrease::class)->makePartial();

        $capacityIncreaseListener->handle(new \App\Events\V2\Volume\Updated($this->volume));

        // sync set to complete by the CapacityIncrease listener
        Event::assertDispatched(\App\Events\V2\Sync\Saved::class, function ($event) use ($sync) {
            return $event->model->id === $sync->id;
        });

        $sync->refresh();
        //exit(print_r($sync));




        $dispatchResourceSyncedEventListener = \Mockery::mock(\App\Listeners\V2\Sync\DispatchResourceSyncedEvent::class)->makePartial();
        $dispatchResourceSyncedEventListener->handle(new \App\Events\V2\Sync\Saved($sync));

//        Event::assertDispatched(\App\Events\V2\Volume\Synced::class, function ($event) {
//            return $event->model->id === $this->volume->id;
//        });

//
//        // DispatchResourceSyncedEvent
//
//        Event::assertDispatched(\App\Events\V2\Volume\Synced::class, function ($event) {
//            return $event->model->id === $this->volume->id;
//        });

        // event(new \App\Events\V2\Volume\Synced($resource));

        //$this->volume->setSyncCompleted();


//        $capacityIncreaseListener->handle(new \App\Events\V2\FloatingIp\Deleted($this->floatingIp));
//
//        Queue::assertPushed(UpdateFloatingIpCapacity::class);
//
//
//        Event::assertDispatched(\App\Events\V2\Sync\Saved::class, function ($event) {
//            return $event->model->id === $this->volume->id;
//        });

    }


//    public function testresizingVolumeUpdatesBilling_()
//    {
//        $newFloatingIp = factory(FloatingIp::class)->create([
//            'ip_address' => $this->faker->ipv4,
//        ]);
//        $this->nat->destination_id = $newFloatingIp->id;
//
//        $mockNsxService = \Mockery::mock();
//        $mockNsxService->shouldReceive('patch')
//            ->once()
//            ->andReturn(new Response(200)); // TODO :- Build on this
//        app()->bind(NsxService::class, function () use ($mockNsxService) {
//            return $mockNsxService;
//        });
//        $listener = \Mockery::mock(\App\Listeners\V2\Nat\Deploy::class)->makePartial();
//        $listener->handle(new \App\Events\V2\Nat\Saved($this->nat));
//
//        $this->nat->save();
//        Event::assertDispatched(\App\Events\V2\Nat\Saving::class, function ($event) {
//            return $event->model->id === $this->nat->id;
//        });
//        Event::assertDispatched(\App\Events\V2\Nat\Saved::class, function ($event) {
//            return $event->model->id === $this->nat->id;
//        });
//    }
}
