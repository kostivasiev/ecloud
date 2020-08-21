<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $network;

    protected $instance;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ]);
        $this->instance = factory(Instance::class)->create([
            'network_id' => $this->network->getKey(),
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'network_id' => $this->network->getKey(),
        ];

        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
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

    public function testNullNameIsDenied()
    {
        $data = [
            'network_id' => ''
        ];

        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The network id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'network_id'
            ])
            ->assertResponseStatus(422);
    }


    public function testNonExistentNetworkId()
    {
        $data = [
            'network_id' => 'net-12345'
        ];

        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified network was not found',
                'status' => 422,
                'source' => 'network_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedNetworkIdIsFailed()
    {
        $data = [
            'network_id'    => $this->network->getKey(),
        ];
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified network id was not found',
                'status' => 422,
                'source' => 'network_id'
            ])
            ->assertResponseStatus(422);
    }


    public function testValidDataIsSuccessful()
    {
        $network = factory(Network::class, 1)->create([
            'name' => 'Manchester Network',
        ])->first();

        $data = [
            'network_id' => $network->getKey(),
        ];
        $this->patch(
            '/v2/instances/' . $this->instance->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $instance = Instance::findOrFail($this->instance->getKey());
        $this->assertEquals($data['network_id'], $instance->network_id);
    }
}
