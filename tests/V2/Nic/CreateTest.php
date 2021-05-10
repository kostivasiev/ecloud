<?php

namespace Tests\V2\Nic;

use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->macAddress = $this->faker->macAddress;
    }

    public function testValidDataSucceeds()
    {
        $this->markTestSkipped('Skipped create NIC endpoint - CRUD endpoint does not deploy yet');

        $macAddress = $this->faker->macAddress;
        $this->post(
            '/v2/nics',
            [
                'mac_address' => $macAddress,
                'instance_id' => $this->instance()->id,
                'network_id' => $this->network()->id,
                'ip_address'  => '10.0.0.5',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'nics',
                [
                    'mac_address' => $macAddress,
                    'instance_id' => $this->instance()->id,
                    'network_id'  => $this->network()->id,
                    'ip_address' => '10.0.0.5'
                ],
                'ecloud'
            )
            ->assertResponseStatus(201);
    }
}
