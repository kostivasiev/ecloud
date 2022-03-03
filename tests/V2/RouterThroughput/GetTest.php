<?php

namespace Tests\V2\RouterThroughput;

use App\Models\V2\RouterThroughput;
use Tests\TestCase;

class GetTest extends TestCase
{
    private RouterThroughput $routerThroughput;

    public function setUp(): void
    {
        parent::setUp();
        $this->routerThroughput = RouterThroughput::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get('/v2/router-throughputs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])
            ->assertJsonFragment([
                'id' => $this->routerThroughput->id,
                'name' => $this->routerThroughput->name,
                'availability_zone_id' => $this->routerThroughput->availability_zone_id,
                "committed_bandwidth" => $this->routerThroughput->committed_bandwidth,
            ])
            ->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/router-throughputs/' . $this->routerThroughput->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])
            ->assertJsonFragment([
                'id' => $this->routerThroughput->id,
                'name' => $this->routerThroughput->name,
                'availability_zone_id' => $this->routerThroughput->availability_zone_id,
                "committed_bandwidth" => $this->routerThroughput->committed_bandwidth,
            ])
            ->assertStatus(200);
    }
}
