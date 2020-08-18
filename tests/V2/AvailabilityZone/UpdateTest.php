<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
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
        $zone = factory(AvailabilityZone::class)->create();
        $data = [
            'code'    => 'MAN2',
            'name'    => 'Manchester Zone 2',
            'datacentre_site_id' => $this->faker->randomDigit(),
        ];
        $this->patch(
            '/v2/availability-zones/' . $zone->getKey(),
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

    public function testNullCodeIsDenied()
    {
        $zone = factory(AvailabilityZone::class)->create();
        $data = [
            'code'    => '',
            'name'    => 'Manchester Zone 2',
            'datacentre_site_id' => $this->faker->randomDigit(),
        ];
        $this->patch(
            '/v2/availability-zones/' . $zone->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The code field, when specified, cannot be null',
                'status' => 422,
                'source' => 'code'
            ])
            ->assertResponseStatus(422);
    }

    public function testNullNameIsDenied()
    {
        $zone = factory(AvailabilityZone::class)->create();
        $data = [
            'code'    => 'MAN2',
            'name'    => '',
            'datacentre_site_id' => $this->faker->randomDigit(),
        ];
        $this->patch(
            '/v2/availability-zones/' . $zone->getKey(),
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

    public function testNullSiteIdIsDenied()
    {
        $zone = factory(AvailabilityZone::class)->create();
        $data = [
            'code'    => 'MAN2',
            'name'    => 'Manchester Zone 2',
            'datacentre_site_id' => '',
        ];
        $this->patch(
            '/v2/availability-zones/' . $zone->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The datacentre site id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'datacentre_site_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $zone = factory(AvailabilityZone::class)->create();
        $data = [
            'code'    => 'MAN2',
            'name'    => 'Manchester Zone 2',
            'datacentre_site_id' => $this->faker->randomDigit(),
        ];
        $this->patch(
            '/v2/availability-zones/' . $zone->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $availabilityZone = AvailabilityZone::findOrFail($zone->getKey());
        $this->assertEquals($data['code'], $availabilityZone->code);
        $this->assertEquals($data['name'], $availabilityZone->name);
        $this->assertEquals($data['datacentre_site_id'], $availabilityZone->datacentre_site_id);
    }
}
