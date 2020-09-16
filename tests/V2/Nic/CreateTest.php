<?php

namespace Tests\V2\Nic;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
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
    }

    public function testNoPermIsDenied()
    {
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ];
        $this->post(
            '/v2/nics',
            $data,
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNoWritePermIsForbidden()
    {
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ];
        $this->post(
            '/v2/nics',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'title'  => 'Forbidden',
                'detail' => 'Forbidden',
                'status' => 403,
            ])
            ->assertResponseStatus(403);
    }

    public function testNotAdminIsDenied()
    {
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ];
        $this->post(
            '/v2/nics',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testInvalidMacAddressFails()
    {
        $data = [
            'mac_address' => 'INVALID_MAC_ADDRESS',
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ];
        $this->post(
            '/v2/nics',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The mac address must be a valid MAC address',
                'status' => 422,
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidInstanceIdFails()
    {
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => 'INVALID_INSTANCE_ID',
            'network_id'  => $this->network->getKey(),
        ];
        $this->post(
            '/v2/nics',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The instance id is not a valid Instance',
                'status' => 422,
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidNetworkIdFails()
    {
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => 'INVALID_NETWORK_ID',
        ];
        $this->post(
            '/v2/nics',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The network id is not a valid Network',
                'status' => 422,
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ];
        $this->post(
            '/v2/nics',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
    }
}
