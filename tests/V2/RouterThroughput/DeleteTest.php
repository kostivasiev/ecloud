<?php

namespace Tests\V2\RouterThroughput;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\RouterThroughput;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    private RouterThroughput $routerThroughput;

    public function setUp(): void
    {
        parent::setUp();

        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey()
        ]);

        $this->routerThroughput = factory(RouterThroughput::class)->create([
            'availability_zone_id' => $availabilityZone->getKey(),
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete('/v2/router-throughputs/' . $this->routerThroughput->getKey(), [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])
            ->assertResponseStatus(204);

        $this->routerThroughput->refresh();
        $this->assertNotNull($this->routerThroughput->deleted_at);
    }
}
