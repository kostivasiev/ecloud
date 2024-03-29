<?php

namespace Tests\V2\Router\EventTests;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DefaultAvailabilityZoneTest extends TestCase
{
    protected Generator $faker;
    protected AvailabilityZone $availabilityZone;
    protected Region $region;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = Region::factory()->create([
            'name' => $this->faker->country(),
        ]);
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id,
        ]);
    }

    public function testCreateRouterWithAvailabilityZone()
    {
        Bus::fake();
        $response = $this->post(
            '/v2/routers',
            [
                'name' => 'Manchester Network',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertStatus(202);
        $id = json_decode($response->getContent())->data->id;
        $router = Router::findOrFail($id);
        // verify that the availability_zone_id equals the one in the data array
        $this->assertEquals($router->availability_zone_id, $this->availabilityZone->id);
    }
}
