<?php

namespace Tests\unit\Router;

use App\Listeners\V2\Router\DefaultRouterThroughput;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DefaultRouterThroughputTest extends TestCase
{
    use DatabaseMigrations;

    protected AvailabilityZone $availabilityZone;
    protected Region $region;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create([]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
    }

    public function testDefaultRouterThroughput()
    {
        $routerThroughput = factory(RouterThroughput::class)->create([
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'committed_bandwidth' => config('router.throughput.default.bandwidth'),
        ]);

        $defaultThroughputListener = \Mockery::mock(DefaultRouterThroughput::class)->makePartial();

        $router = factory(Router::class)->make([
            'id' => 'rtr-abc123',
            'availability_zone_id' => $this->availabilityZone->getKey(),
        ]);

        $defaultThroughputListener->handle(new \App\Events\V2\Router\Creating($router));

        $this->assertEquals($routerThroughput->id, $router->router_throughput_id);
    }
}
