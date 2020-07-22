<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
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

        $this->availabilityZone = factory(AvailabilityZone::class, 1)->create([
        ])->first();

        $this->network = factory(Network::class, 1)->create([
            'name'    => 'Manchester Network',
            'router_id' => $this->router->getKey(),
            'availability_zone_id' =>  $this->availabilityZone->getKey()
        ])->first();

        $this->network = factory(Network::class, 1)->create([
            'name'    => 'Manchester Network',
            'router_id'=> $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ])->first();

        $this->instance = factory(Instance::class, 1)->create([
            'network_id'    => $this->network->getKey(),
        ])->first();
    }

    public function testNetworkRelation()
    {
        $this->assertInstanceOf(Network::class, $this->instance->network);
        $this->assertEquals($this->network->getKey(), $this->instance->network->getKey());
    }
}
