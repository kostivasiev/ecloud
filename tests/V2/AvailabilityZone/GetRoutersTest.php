<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use Faker\Factory as Faker;
use Tests\TestCase;

class GetRoutersTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected AvailabilityZone $availabilityZone;
    protected Router $router;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $region = Region::factory()->create();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $region->id
        ]);

        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/availability-zones/'.$this->availabilityZone->id.'/routers',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id'       => $this->router->id,
                'name'     => $this->router->name,
                'vpc_id'   => $this->router->vpc_id,
            ])
            ->assertStatus(200);
    }
}
