<?php

namespace Tests\V2\AvailabilityZones;

use App\Models\V2\AvailabilityZones;
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
        $zone = $this->createZone();
        $data = [
            'code'    => 'MAN2',
            'name'    => 'Manchester Zone 2',
            'site_id' => $this->faker->randomDigit(),
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
        $zone = $this->createZone();
        $data = [
            'code'    => '',
            'name'    => 'Manchester Zone 2',
            'site_id' => $this->faker->randomDigit(),
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
        $zone = $this->createZone();
        $data = [
            'code'    => 'MAN2',
            'name'    => '',
            'site_id' => $this->faker->randomDigit(),
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
        $zone = $this->createZone();
        $data = [
            'code'    => 'MAN2',
            'name'    => 'Manchester Zone 2',
            'site_id' => '',
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
                'detail' => 'The site id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'site_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $zone = $this->createZone();
        $data = [
            'code'    => 'MAN2',
            'name'    => 'Manchester Zone 2',
            'site_id' => $this->faker->randomDigit(),
        ];
        $this->patch(
            '/v2/availability-zones/' . $zone->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);

        $availabilityZone = AvailabilityZones::findOrFail($zone->getKey());
        $this->assertEquals($data['code'], $availabilityZone->code);
        $this->assertEquals($data['name'], $availabilityZone->name);
        $this->assertEquals($data['site_id'], $availabilityZone->site_id);
    }

    /**
     * Create Availability Zone
     * @return \App\Models\V2\AvailabilityZones
     */
    public function createZone(): AvailabilityZones
    {
        $zone = factory(AvailabilityZones::class, 1)->create()->first();
        $zone->save();
        $zone->refresh();
        return $zone;
    }

}