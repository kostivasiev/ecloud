<?php

namespace Tests\V2\Region;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
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

        $this->regions = factory(Region::class, 2)->create([
            'name'    => $this->faker->country(),
        ])->each(function ($region) {
            factory(AvailabilityZone::class, 2)->create([
                'region_id' => $region->getKey(),
                'name' => $this->faker->city()
            ]);
        });
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/regions',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $this->regions->first()->getKey(),
                'name'         => $this->regions->first()->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItem()
    {
        $this->get(
            '/v2/regions/' . $this->regions->first()->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $this->regions->first()->getKey(),
                'name'       => $this->regions->first()->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetRegionAvailabilityZones()
    {
        $availabilityZones = $this->regions->first()->availabilityZones()->get();

        $this->get(
            '/v2/regions/' . $this->regions->first()->getKey() . '/availability-zones',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $availabilityZones->first()->getKey(),
                'name'       => $availabilityZones->first()->name,
            ])
            ->assertResponseStatus(200);
    }

}
