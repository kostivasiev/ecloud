<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Router;
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

        $this->router = factory(Router::class, 1)->create([
            'name'       => 'Manchester Router 1',
        ])->first();

        $this->availabilityZone = factory(AvailabilityZone::class, 1)->create([
        ])->first();

        $this->networks = factory(Network::class, 3)->create([
            'name'    => 'Manchester Network',
            'router_id'=> $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
    }

    public function testNetworksRelation()
    {
        $this->assertEquals(3, $this->availabilityZone->networks->count());
        $this->assertInstanceOf(Network::class, $this->availabilityZone->networks->first());
        $this->assertEquals($this->networks->first()->getKey(), $this->availabilityZone->networks->first()->getKey());
    }
}
