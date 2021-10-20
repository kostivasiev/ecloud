<?php

namespace Tests\V2\Router\EventTests;

use App\Listeners\V2\TaskCreated;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
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
        $this->region = factory(Region::class)->create([
            'name' => $this->faker->country(),
        ]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
    }

    public function testCreateRouterWithAvailabilityZone()
    {
        Event::fake(TaskCreated::class);
        Bus::fake();
        $this->post(
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
            ->assertResponseStatus(202);
        $id = json_decode($this->response->getContent())->data->id;
        $router = Router::findOrFail($id);
        // verify that the availability_zone_id equals the one in the data array
        $this->assertEquals($router->availability_zone_id, $this->availabilityZone->id);
    }
}
