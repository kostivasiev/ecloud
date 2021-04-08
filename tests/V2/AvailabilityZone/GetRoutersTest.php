<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetRoutersTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected AvailabilityZone $availabilityZone;
    protected Router $router;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->id
        ]);

        $this->router = factory(Router::class)->create([
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
            ->seeJson([
                'id'       => $this->router->id,
                'name'     => $this->router->name,
                'vpc_id'   => $this->router->vpc_id,
            ])
            ->assertResponseStatus(200);
    }
}
