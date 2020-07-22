<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Router;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class RelationshipTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->router = factory(Router::class, 1)->create([
            'name'       => 'Manchester Router 1',
        ])->first();

        $this->availabilityZone = factory(AvailabilityZone::class, 1)->create([
        ])->first();

        $this->network = factory(Network::class, 1)->create([
            'name'    => 'Manchester Network',
            'router_id'=> $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ])->first();
    }

    public function testRouterRelation()
    {
        $this->assertInstanceOf(Router::class, $this->network->router);
        $this->assertEquals($this->network->router->getKey(), $this->router->getKey());
    }

    public function testAvailabilityZoneRelation()
    {
        $this->assertInstanceOf(AvailabilityZone::class, $this->network->availabilityZone);
        $this->assertEquals($this->network->availabilityZone->getKey(), $this->availabilityZone->getKey());
    }
}
