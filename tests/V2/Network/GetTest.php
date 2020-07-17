<?php

namespace Tests\V2\Network;

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
    }

    public function testNoPermsIsDenied()
    {
        $this->get(
            '/v2/networks',
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
        $network = factory(Network::class, 1)->create([
            'name'    => 'Manchester Network',
        ])->first();
        $this->get(
            '/v2/networks',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $network->id,
                'name'       => $network->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $network = factory(Network::class, 1)->create([
            'name'    => 'Manchester Network',
        ])->first();
        $network->save();
        $network->refresh();

        $this->get(
            '/v2/networks/' . $network->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $network->id,
                'name'       => $network->name,
            ])
            ->assertResponseStatus(200);
    }

}
