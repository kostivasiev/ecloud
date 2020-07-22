<?php

namespace Tests\V2\Vpn;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class RelationshipTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone = factory(AvailabilityZone::class, 1)->create([
        ])->first();


        $this->router = factory(Router::class, 1)->create([
            'name'       => 'Manchester Router 1',
        ])->first();

        $this->vpn = factory(Vpn::class, 2)->create([
            'router_id'            => $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
        ])->first();

    }

    public function testRouterRelation()
    {
        $this->assertInstanceOf(Router::class, $this->vpn->router);
        $this->assertEquals($this->router->getKey(), $this->vpn->router->getKey());
    }

    public function testAvailabilityZoneRelation()
    {
        $this->assertInstanceOf(AvailabilityZone::class, $this->vpn->availabilityZone);
        $this->assertEquals($this->availabilityZone->getKey(), $this->vpn->availabilityZone->getKey());
    }

    public function testRouterVpnRelation()
    {
        $this->assertInstanceOf(Vpn::class, $this->router->vpns->first());
        $this->assertEquals($this->vpn->getKey(), $this->router->vpns->first()->getKey());
    }

    public function testAvailabilityZoneVpnRelation()
    {
        $this->assertInstanceOf(Vpn::class, $this->availabilityZone->vpns->first());
        $this->assertEquals($this->availabilityZone->vpns->first()->getKey(), $this->availabilityZone->vpns->first()->getKey());
    }
}
