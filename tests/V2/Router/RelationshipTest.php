<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Gateway;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
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

        $this->vpc = factory(Vpc::class, 1)->create([
            'name'    => 'Manchester DC',
        ])->first();

        $this->router = factory(Router::class, 1)->create([
            'name'       => 'Manchester Router 1',
            'vpc_id' => $this->vpc->getKey()
        ])->first();


        $this->gateway = factory(Gateway::class, 1)->create([
            'name'       => 'Manchester Gateway 1',
        ])->first();

        $this->router->gateways()->attach($this->gateway);

        $this->availabilityZone = factory(AvailabilityZone::class, 1)->create([
        ])->first();

        $this->router->availabilityZones()->attach($this->availabilityZone);
    }

    public function testRouterVpcRelation()
    {
        $this->assertInstanceOf(Vpc::class, $this->router->vpc);
        $this->assertEquals($this->vpc->getKey(), $this->router->vpc->getKey());
    }

    public function testRouterGatewaysRelation()
    {
        $this->assertInstanceOf(Gateway::class, $this->router->gateways->first());
        $this->assertEquals($this->gateway->getKey(), $this->router->gateways->first()->getKey());
    }

    public function testRouterAvailabilityZoneRelation()
    {
        $this->assertInstanceOf(AvailabilityZone::class, $this->router->availabilityZones->first());
        $this->assertEquals($this->availabilityZone->getKey(), $this->router->availabilityZones->first()->getKey());
    }

    public function testGatewaysRoutersRelation()
    {
        $this->assertInstanceOf(Router::class, $this->gateway->routers->first());
        $this->assertEquals($this->router->getKey(), $this->gateway->routers->first()->getKey());
    }

    public function testAvailabilityZonesRoutersRelation()
    {
        $this->assertInstanceOf(Router::class, $this->availabilityZone->routers->first());
        $this->assertEquals($this->router->getKey(), $this->availabilityZone->routers->first()->getKey());
    }
}
