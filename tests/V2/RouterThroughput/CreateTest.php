<?php

namespace Tests\V2\RouterThroughput;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    private Router $router;

    private AvailabilityZone $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();

        $region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey()
        ]);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => '10Gb',
            'availability_zone_id' => $this->availabilityZone->getKey(),
            "committed_bandwidth" => 10240,
            "burst_size" => 1024
        ];

        $this->post('/v2/router-throughputs', $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])
            ->seeInDatabase('router_throughputs', $data, 'ecloud')
            ->assertResponseStatus(201);
    }
}