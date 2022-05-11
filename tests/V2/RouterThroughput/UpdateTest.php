<?php

namespace Tests\V2\RouterThroughput;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\RouterThroughput;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    private Region $region;

    private RouterThroughput $routerThroughput;

    public function setUp(): void
    {
        parent::setUp();

        $this->region = Region::factory()->create();
        $availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);

        $this->routerThroughput = RouterThroughput::factory()->create([
            'availability_zone_id' => $availabilityZone->id,
        ]);
    }

    public function testValidDataSucceeds()
    {
        $availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);

        $this->patch('/v2/router-throughputs/' . $this->routerThroughput->id, [
            'name' => 'NEW NAME',
            'availability_zone_id' => $availabilityZone->id,
            "committed_bandwidth" => 999,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])
            ->assertStatus(200);
        $this->assertDatabaseHas(
            'router_throughputs',
            [
                'id' => $this->routerThroughput->id,
                'name' => 'NEW NAME',
                'availability_zone_id' => $availabilityZone->id,
                "committed_bandwidth" => 999,
            ],
            'ecloud'
        );
    }
}
