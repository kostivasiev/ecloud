<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use App\Models\V2\Network;
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
        $this->network = factory(Network::class, 1)->create([
            'name'    => 'Manchester Network',
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
