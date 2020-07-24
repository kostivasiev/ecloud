<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
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
    }

    public function testGetCollection()
    {
        $availabilityZone = factory(AvailabilityZone::class, 1)->create([
            'code'    => 'MAN1',
            'name'    => 'Manchester Region 1',
            'datacentre_site_id' => 1,
        ])->first();
        $this->get(
            '/v2/availability-zones',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $availabilityZone->id,
                'code'       => $availabilityZone->code,
                'name'       => $availabilityZone->name,
                'datacentre_site_id'    => $availabilityZone->datacentre_site_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetCollectionNonAdminPropertiesHidden()
    {
        $availabilityZone = factory(AvailabilityZone::class, 1)->create([
            'code'    => 'MAN1',
            'name'    => 'Manchester Region 1',
            'datacentre_site_id' => 1,
        ])->first();
        $this->get(
            '/v2/availability-zones',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $availabilityZone->id,
                'code'       => $availabilityZone->code,
                'name'       => $availabilityZone->name,
                'datacentre_site_id'    => $availabilityZone->datacentre_site_id,
            ])
            ->dontSeeJson([
                'is_public' => true
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $availabilityZone = factory(AvailabilityZone::class, 1)->create([
            'code'    => 'MAN1',
            'name'    => 'Manchester Region 1',
            'datacentre_site_id' => 1,
        ])->first();
        $availabilityZone->save();
        $availabilityZone->refresh();

        $this->get(
            '/v2/availability-zones/' . $availabilityZone->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $availabilityZone->id,
                'code'       => $availabilityZone->code,
                'name'       => $availabilityZone->name,
                'datacentre_site_id'    => (int) $availabilityZone->datacentre_site_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetailNonAdminPropertiesHidden()
    {
        $availabilityZone = factory(AvailabilityZone::class, 1)->create([
            'code'    => 'MAN1',
            'name'    => 'Manchester Region 1',
            'datacentre_site_id' => 1,
        ])->first();
        $availabilityZone->save();
        $availabilityZone->refresh();

        $this->get(
            '/v2/availability-zones/' . $availabilityZone->getKey(),
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $availabilityZone->id,
                'code'       => $availabilityZone->code,
                'name'       => $availabilityZone->name,
                'datacentre_site_id'    => (int) $availabilityZone->datacentre_site_id,
            ])
            ->dontSeeJson([
                'is_public' => true
            ])
            ->assertResponseStatus(200);
    }

}
