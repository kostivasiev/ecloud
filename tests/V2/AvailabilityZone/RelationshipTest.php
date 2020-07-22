<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Gateway;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
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

        $this->availabilityZone = factory(AvailabilityZone::class, 1)->create([
        ])->first();

        $this->networks = factory(Network::class, 3)->create([
            'name'    => 'Manchester Network',
            'router_id'=> $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);

        $this->gateway = factory(Gateway::class, 1)->create([
            'availability_zone_id'       => $this->availabilityZone->getKey(),
        ])->first();
    }

    public function testNetworksRelation()
    {
        $this->assertEquals(3, $this->availabilityZone->networks->count());
        $this->assertInstanceOf(Network::class, $this->availabilityZone->networks->first());
        $this->assertEquals($this->networks->first()->getKey(), $this->availabilityZone->networks->first()->getKey());
    }

    public function testGatewaysRelation()
    {
        $this->assertInstanceOf(Gateway::class, $this->availabilityZone->gateways->first());
        $this->assertEquals($this->gateway->first()->getKey(), $this->availabilityZone->gateways->first()->getKey());
    }
}
