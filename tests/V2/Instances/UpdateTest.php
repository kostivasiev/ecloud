<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $vpc;

    protected $instance;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester Vpc',
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $data = [
            'vpc_id' => $this->vpc->getKey(),
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

    public function testNonExistentNetworkId()
    {
        $data = [
            'vpc_id' => 'vpc-12345'
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
                'detail' => 'No valid Vpc record found for specified vpc id',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $vpc = factory(Vpc::class)->create([
            'name' => 'Manchester Network',
        ]);

        $data = [
            'vpc_id' => $vpc->getKey(),
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
        $this->assertEquals($data['vpc_id'], $instance->vpc_id);
    }
}
