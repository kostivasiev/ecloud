<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
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

    public function testNonAdminIsDenied()
    {
        $data = [
            'code'    => 'MAN1',
            'name'    => 'Manchester Zone 1',
            'datacentre_site_id' => $this->faker->randomDigit(),
        ];
        $this->post(
            '/v2/availability-zones',
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

    public function testNullCodeIsFailed()
    {
        $data = [
            'name'    => 'Manchester Zone 1',
            'datacentre_site_id' => $this->faker->randomDigit(),
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The code field is required',
                'status' => 422,
                'source' => 'code'
            ])
            ->assertResponseStatus(422);
    }

    public function testNullNameIsFailed()
    {
        $data = [
            'code'    => 'MAN1',
            'datacentre_site_id' => $this->faker->randomDigit(),
        ];
        $this->post(
            '/v2/availability-zones',
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

    public function testNullSiteIdIsFailed()
    {
        $data = [
            'code'    => 'MAN1',
            'name'    => 'Manchester Zone 1',
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The datacentre site id field is required',
                'status' => 422,
                'source' => 'datacentre_site_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'code'    => 'MAN1',
            'name'    => 'Manchester Zone 1',
            'datacentre_site_id' => $this->faker->randomDigit(),
            'is_public' => false
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $availabilityZoneId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $availabilityZoneId,
        ]);

        $resource = AvailabilityZone::findOrFail($availabilityZoneId);
        $this->assertFalse((boolean) $resource->is_public);
    }

}
