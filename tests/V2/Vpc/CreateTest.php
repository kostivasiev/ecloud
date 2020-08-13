<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class CreateTest extends TestCase
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
        $data = [
            'name'    => 'Manchester DC',
        ];
        $this->post(
            '/v2/vpcs',
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

    public function testNullNameDefaultsToId()
    {
        $data = [
            'name'    => '',
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertResponseStatus(201);

        $virtualPrivateCloudId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $virtualPrivateCloudId,
        ]);

        $vpc = Vpc::findOrFail($virtualPrivateCloudId);
        $this->assertEquals($virtualPrivateCloudId, $vpc->name);
    }

    public function testNotScopedFails()
    {
        $data = [
            'name'    => $this->faker->word(),
            'reseller_id' => 1
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Bad Request',
                'detail' => 'Missing Reseller scope',
                'status' => 400,
            ])
            ->assertResponseStatus(400);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name'    => $this->faker->word(),
            'reseller_id' => 1
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )
            ->seeInDatabase(
                'virtual_private_clouds',
                $data,
                'ecloud'
            )
            ->assertResponseStatus(201);

        $virtualPrivateCloudId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $virtualPrivateCloudId,
        ]);
    }

}
