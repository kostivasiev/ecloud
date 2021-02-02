<?php

namespace Tests\V2\RouterThroughput;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\RouterThroughput;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    private Region $region;

    private RouterThroughput $routerThroughput;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->routerThroughput = factory(RouterThroughput::class)->create([
            'availability_zone_id' => $availabilityZone->getKey(),
        ]);
    }

    public function testValidDataSucceeds()
    {
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->patch('/v2/router-throughputs/' . $this->routerThroughput->getKey(), [
            'name' => 'NEW NAME',
            'availability_zone_id' => $availabilityZone->getKey(),
            "committed_bandwidth" => 999,
            "burst_size" => 888
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])
            ->seeInDatabase('router_throughputs', [
                'id' => $this->routerThroughput->getKey(),
                'name' => 'NEW NAME',
                'availability_zone_id' => $availabilityZone->getKey(),
                "committed_bandwidth" => 999,
                "burst_size" => 888
            ],
                'ecloud'
            )
            ->assertResponseStatus(200);
    }
}