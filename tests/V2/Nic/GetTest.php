<?php

namespace Tests\V2\Nic;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $nic;
    protected $instance;
    protected $network;
    protected $macAddress;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->macAddress = $this->faker->macAddress;
        Vpc::flushEventListeners();
        $vpc = factory(Vpc::class)->create([
            'name' => 'Manchester VPC',
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $vpc->getKey(),
        ]);
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ]);
        $this->nic = factory(Nic::class)->create([
            'mac_address' => $this->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ]);
    }

    public function testNoPermIsDenied()
    {
        $this->get(
            '/v2/nics',
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNoAdminIsDenied()
    {
        $this->get(
            '/v2/nics',
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups'    => 'ecloud.read',
            ]
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
            '/v2/nics',
            [
                'X-consumer-custom_id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'mac_address' => $this->macAddress,
                'instance_id' => $this->instance->getkey(),
                'network_id' => $this->network->getKey(),
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/nics/' . $this->nic->getKey(),
            [
                'X-consumer-custom_id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'mac_address' => $this->macAddress,
                'instance_id' => $this->instance->getkey(),
                'network_id' => $this->network->getKey(),
            ])
            ->assertResponseStatus(200);
    }
}
