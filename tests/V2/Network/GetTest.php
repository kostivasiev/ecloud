<?php

namespace Tests\V2\Network;

use App\Models\V2\Network;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNoPermsIsDenied()
    {
        $this->get(
            '/v2/networks',
            []
        )
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testGetCollection()
    {
        $network = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ]);
        $this->get(
            '/v2/networks',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $network->id,
                'name' => $network->name,
                'subnet' => '10.0.0.0/24'
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $network = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ]);
        $this->get(
            '/v2/networks/' . $network->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $network->id,
                'name' => $network->name,
                'subnet' => '10.0.0.0/24'
            ])
            ->assertResponseStatus(200);
    }

}
