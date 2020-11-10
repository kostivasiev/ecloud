<?php

namespace Tests\unit\Listeners\Vpc;

use App\Events\V2\Vpc\Deleted;
use App\Listeners\V2\Vpc\Routers\Delete as DeleteRouters;
use App\Listeners\V2\Vpc\FloatingIps\Delete as DeleteFloatingIps;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\FloatingIp;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteRouterTest extends TestCase
{
    use DatabaseMigrations;

    protected AvailabilityZone $availabilityZone;
    protected Dhcp $dhcp;
    protected FloatingIp $floatingIp;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $this->floatingIp = factory(FloatingIp::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
    }

    public function testDeletingVpcDeletesRouter()
    {
        Event::fake(Deleted::class);
        $listener = \Mockery::mock(DeleteRouters::class)->makePartial();
        $listener->handle(new Deleted($this->vpc));
        $this->router->refresh();
        $this->assertNotNull($this->router->deleted_at);
    }

    public function testDeletingVpcDeletesFloatingIp()
    {
        Event::fake(Deleted::class);
        $listener = \Mockery::mock(DeleteFloatingIps::class)->makePartial();
        $listener->handle(new Deleted($this->vpc));
        $this->floatingIp->refresh();
        $this->assertNotNull($this->floatingIp->deleted_at);
    }
}