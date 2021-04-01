<?php

namespace Tests\unit\Router;

use App\Listeners\V2\Router\DefaultRouterThroughput;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DefaultRouterThroughputTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testDefaultRouterThroughput()
    {
        $routerThroughput = factory(RouterThroughput::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'committed_bandwidth' => config('router.throughput.default.bandwidth')
        ]);

        $defaultThroughputListener = \Mockery::mock(DefaultRouterThroughput::class)->makePartial();

        $router = factory(Router::class)->make([
            'availability_zone_id' => $this->availabilityZone()->id,
            'router_throughput_id' => null
        ]);

        $defaultThroughputListener->handle(new \App\Events\V2\Router\Creating($router));

        $this->assertEquals($routerThroughput->id, $router->router_throughput_id);
    }
}
