<?php

namespace Tests\V2\Gateway;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Gateway;
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

        $this->availabilityZone = factory(AvailabilityZone::class)->create();
    }

    public function testNonAdminIsDenied()
    {
        $data = [
            'name'    => 'Manchester Gateway 1',
            'availability_zone_id'    => $this->availabilityZone->getKey(),
        ];
        $this->post(
            '/v2/gateways',
            $data,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testNullNameIsFailed()
    {
        $data = [
            'name' => '',
            'availability_zone_id'    => $this->availabilityZone->getKey()
        ];
        $this->post(
            '/v2/gateways',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The name field is required',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidAvailabilityZoneIdUndefinedIsFailed()
    {
        $data = [
            'name'    => 'Manchester Gateway 1',
        ];

        $this->post(
            '/v2/networks',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The availability zone id field is required',
                'status' => 422,
                'source' => 'availability_zone_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidAvailabilityZoneIdIsFailed()
    {
        $data = [
            'name'    => 'Manchester Gateway 1',
            'availability_zone_id'    => $this->faker->uuid()
        ];

        $this->post(
            '/v2/networks',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified availability zone id was not found',
                'status' => 422,
                'source' => 'availability_zone_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name'    => 'Manchester Gateway 1',
            'availability_zone_id'    => $this->availabilityZone->getKey(),
        ];
        $this->post(
            '/v2/gateways',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $gatewayId = (json_decode($this->response->getContent()))->data->id;
        $gateway = Gateway::find($gatewayId);
        $this->assertNotNull($gateway);
    }

}
