<?php

namespace Tests\V2\Gateway;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Gateway;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
        ]);
    }

    public function testNonAdminIsDenied()
    {
        $gateway = factory(Gateway::class)->create([
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name' => 'Manchester Gateway 2',
            'availability_zone_id'    => $this->availabilityZone->getKey()
        ];
        $this->patch(
            '/v2/gateways/' . $gateway->getKey(),
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

    public function testNullNameIsDenied()
    {
        $gateway = factory(Gateway::class)->create([
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name' => '',
            'availability_zone_id'    => $this->availabilityZone->getKey()
        ];
        $this->patch(
            '/v2/gateways/' . $gateway->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The name field, when specified, cannot be null',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidAvailabilityZoneIdIsFailed()
    {
        $gateway = factory(Gateway::class)->create([
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name'    => 'Manchester Gateway 1',
            'availability_zone_id'    => $this->faker->uuid()
        ];

        $this->patch(
            '/v2/gateways/' . $gateway->getKey(),
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

    public function testValidDataIsSuccessful()
    {
        $gateway = factory(Gateway::class)->create([
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name' => 'Manchester Gateway 2',
            'availability_zone_id'    => $this->availabilityZone->getKey()
        ];
        $this->patch(
            '/v2/gateways/' . $gateway->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $gatewayItem = Gateway::findOrFail($gateway->getKey());
        $this->assertEquals($data['name'], $gatewayItem->name);
    }
}
