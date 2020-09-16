<?php

namespace Tests\V2\Nic;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
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
        $this->delete(
            '/v2/nics/' . $this->nic->getKey(),
            [],
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
        $this->delete(
            '/v2/nics/' . $this->nic->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
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
        $this->delete(
            '/v2/nics/' . $this->nic->getKey(),
            [],
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

    public function testInvalidNicFails()
    {
        $this->delete(
            '/v2/nics/INVALID-NIC-ID',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Nic with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testValidNicSucceeds()
    {
        $this->delete(
            '/v2/nics/' . $this->nic->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
    }
}
