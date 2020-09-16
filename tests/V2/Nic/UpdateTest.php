<?php

namespace Tests\V2\Nic;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $instance;
    protected $network;
    protected $macAddress;
    protected $nic;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->macAddress = $this->faker->macAddress;
        Vpc::flushEventListeners();
        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester VPC',
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ]);
        $this->nic = factory(Nic::class)->create([
            'mac_address' => $this->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ])->refresh();
    }

    public function testNoPermIsDenied()
    {
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ];
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
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

    public function testNoWritePermIsDenied()
    {
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ];
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
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

    public function testNoAdminIsDenied()
    {
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id'  => $this->network->getKey(),
        ];
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
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
        ];
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
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
            'instance_id' => 'INVALID_INSTANCE_ID',
        ];
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
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
            'network_id'  => 'INVALID_NETWORK_ID',
        ];
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
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

    public function testValidDataIsSuccessful()
    {
        $newInstance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ])->refresh();
        $newNetwork = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ])->refresh();
        $data = [
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $newInstance->getKey(),
            'network_id'  => $newNetwork->getKey(),
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
