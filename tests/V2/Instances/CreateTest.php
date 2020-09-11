<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester VPC',
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
        ];
        $this->post(
            '/v2/instances',
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

    public function testNotOwnedNetworkIdIsFailed()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
        ];
        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        // No name defined - defaults to ID
        $data = [
            'vpc_id' => $this->vpc->getKey(),
        ];
        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $id
        ])
            ->seeInDatabase(
                'instances',
                [
                'id' => $id,
                'name' => $id,
                ],
                'ecloud'
            );

        // Name defined
        $name = $this->faker->word();
        $data = [
            'vpc_id' => $this->vpc->getKey(),
            'name' => $name
        ];

        $this->post(
            '/v2/instances',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $id,
        ])
            ->seeInDatabase(
                'instances',
                [
                    'id' => $id,
                    'name' => $name,
                ],
                'ecloud'
            );
    }
}
