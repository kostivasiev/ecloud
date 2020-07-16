<?php

namespace Tests\V2\VirtualPrivateClouds;

use App\Models\V2\VirtualPrivateClouds;
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
            '/v2/vpcs',
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
        $virtualPrivateCloud = factory(VirtualPrivateClouds::class, 1)->create([
            'name'    => 'Manchester DC',
        ])->first();
        $this->get(
            '/v2/vpcs',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $virtualPrivateCloud->id,
                'name'       => $virtualPrivateCloud->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $virtualPrivateCloud = factory(VirtualPrivateClouds::class, 1)->create([
            'name'    => 'Manchester DC',
        ])->first();
        $virtualPrivateCloud->save();
        $virtualPrivateCloud->refresh();

        $this->get(
            '/v2/vpcs/' . $virtualPrivateCloud->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $virtualPrivateCloud->id,
                'name'       => $virtualPrivateCloud->name,
            ])
            ->assertResponseStatus(200);
    }

}
