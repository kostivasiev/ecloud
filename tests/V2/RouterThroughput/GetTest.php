<?php

namespace Tests\V2\RouterThroughput;

use App\Models\V2\RouterThroughput;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    private RouterThroughput $routerThroughput;

    public function setUp(): void
    {
        parent::setUp();
        $this->routerThroughput = factory(RouterThroughput::class)->create([
            'availability_zone_id' => $this->availabilityZone()->getKey(),
        ]);
    }

    public function testGetCollection()
    {
        $this->get('/v2/router-throughputs', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])
            ->seeJson([
                'id' => $this->routerThroughput->getKey(),
                'name' => $this->routerThroughput->name,
                'availability_zone_id' => $this->routerThroughput->availability_zone_id,
                "committed_bandwidth" => $this->routerThroughput->committed_bandwidth,
                "burst_size" => $this->routerThroughput->burst_size
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/router-throughputs/' . $this->routerThroughput->getKey(), [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read, ecloud.write',
        ])
            ->seeJson([
                'id' => $this->routerThroughput->getKey(),
                'name' => $this->routerThroughput->name,
                'availability_zone_id' => $this->routerThroughput->availability_zone_id,
                "committed_bandwidth" => $this->routerThroughput->committed_bandwidth,
                "burst_size" => $this->routerThroughput->burst_size
            ])
            ->assertResponseStatus(200);
    }
}
