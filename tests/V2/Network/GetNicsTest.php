<?php

namespace Tests\V2\Network;

use App\Models\V2\Nic;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetNicsTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testGetCollection()
    {
        $nic = factory(Nic::class)->create([
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance()->id,
            'network_id' => $this->network()->id,
        ]);

        $this->get(
            '/v2/networks/' . $this->network()->id . '/nics',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'mac_address' => $nic->mac_address,
                'instance_id' => $nic->instance_id,
                'network_id'  => $nic->network_id,
            ])
            ->assertResponseStatus(200);
    }
}
