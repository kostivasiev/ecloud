<?php

namespace Tests\unit\Listeners\V2\Router;

use App\Listeners\V2\Router\DefaultRouterThroughput;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DefaultRouterThroughputTest extends TestCase
{
    private Router $router;

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

        $this->router = factory(Router::class)->make([
            'availability_zone_id' => $this->availabilityZone()->id,
            'router_throughput_id' => null
        ]);

        $defaultThroughputListener->handle(new \App\Events\V2\Router\Creating($this->router));

        $this->assertEquals($routerThroughput->id, $this->router->router_throughput_id);
    }
}
