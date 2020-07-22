<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->network = factory(Network::class, 1)->create([
            'name'    => 'Manchester Network',
        ])->first();
        $this->instance = factory(Instance::class, 1)->create([
            'network_id'    => $this->network->getKey(),
        ])->first();
    }

    public function testNoPermsIsDenied()
    {
        $this->get(
            '/v2/instances',
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->instance->getKey(),
                'network_id' => $this->instance->network_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/instances/' . $this->instance->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->instance->getKey(),
                'network_id' => $this->instance->network_id,
            ])
            ->assertResponseStatus(200);
    }
}
