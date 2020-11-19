<?php

namespace Tests\V2\Nic;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $instance;
    protected $macAddress;
    protected $network;
    protected $nic;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->macAddress = $this->faker->macAddress;
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
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
            'network_id' => $this->network->getKey(),
        ])->refresh();
    }

    public function testInvalidMacAddressFails()
    {
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
            [
                'mac_address' => 'INVALID_MAC_ADDRESS',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The mac address must be a valid MAC address',
                'status' => 422,
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidInstanceIdFails()
    {
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
            [
                'instance_id' => 'INVALID_INSTANCE_ID',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The instance id is not a valid Instance',
                'status' => 422,
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidNetworkIdFails()
    {
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
            [
                'network_id' => 'INVALID_NETWORK_ID',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The network id is not a valid Network',
                'status' => 422,
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $this->patch(
            '/v2/nics/' . $this->nic->getKey(),
            [
                'mac_address' => $this->macAddress,
                'instance_id' => $this->instance->getKey(),
                'network_id' => $this->network->getKey(),
                'ip_address' => '10.0.0.6'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'nics',
                [
                    'id' => $this->nic->getKey(),
                    'mac_address' => $this->macAddress,
                    'instance_id' => $this->instance->getKey(),
                    'network_id'  => $this->network->getKey(),
                    'ip_address' => '10.0.0.6'
                ],
                'ecloud'
            )
            ->assertResponseStatus(200);
    }
}
