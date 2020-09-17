<?php

namespace Tests\V2\Router;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $region;
    protected $router;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/routers',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $this->router->getKey(),
                'name'   => $this->router->name,
                'vpc_id' => $this->router->vpc_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/routers/' . $this->router->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $this->router->id,
                'name'   => $this->router->name,
                'vpc_id' => $this->router->vpc_id
            ])
            ->assertResponseStatus(200);
    }

}
