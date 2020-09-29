<?php

namespace Tests\V2\Region;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $regions;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->regions = factory(Region::class, 2)->create([
            'name'    => $this->faker->country(),
        ])->each(function ($region) {
            factory(AvailabilityZone::class, 2)->create([
                'region_id' => $region->getKey(),
                'name' => $this->faker->city(),
                'is_public' => false,
            ]);
        });
    }

    public function testGetCollectionAsAdmin()
    {
        $region = $this->regions->first();
        $region->is_public = true;
        $region->save();

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

        $this->assertCount(2, $this->response->original);
    }

    public function testGetCollectionAsNonAdmin()
    {
        $region = $this->regions->first();
        $region->is_public = true;
        $region->save();

        $this->get(
            '/v2/regions',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $this->regions->first()->getKey(),
                'name'         => $this->regions->first()->name,
            ])

            ->assertResponseStatus(200);

        $this->assertCount(1, $this->response->original);
    }

    public function testGetPublicRegionAsAdmin()
    {
        $region = $this->regions->first();
        $region->is_public = true;
        $region->save();

        $this->get(
            '/v2/regions/' . $region->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $region->getKey(),
                'name'       => $region->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetPublicRegionAsNonAdmin()
    {
        $region = $this->regions->first();
        $region->is_public = true;
        $region->save();

        $this->get(
            '/v2/regions/' . $region->getKey(),
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $region->getKey(),
                'name'       => $region->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetPrivateRegionAsAdmin()
    {
        $region = $this->regions->first();
        $region->is_public = false;
        $region->save();

        $this->get(
            '/v2/regions/' . $region->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $region->getKey(),
                'name'       => $region->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetPrivateRegionAsNonAdmin()
    {
        $region = $this->regions->first();
        $region->is_public = false;
        $region->save();

        $this->get(
            '/v2/regions/' . $region->getKey(),
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertResponseStatus(404);
    }

    public function testGetPublicRegionAvailabilityZonesAsNonAdmin()
    {
        $region = $this->regions->first();
        $region->is_public = true;
        $region->save();

        $availabilityZones = $region->availabilityZones()->get();

        $this->get(
            '/v2/regions/' . $region->getKey() . '/availability-zones',
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

    public function testGetPublicRegionAvailabilityZonesAsAdmin()
    {
        $region = $this->regions->first();
        $region->is_public = true;
        $region->save();

        $availabilityZones = $region->availabilityZones()->get();

        $this->get(
            '/v2/regions/' . $region->getKey() . '/availability-zones',
            [
                'X-consumer-custom-id' => '0-0',
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
