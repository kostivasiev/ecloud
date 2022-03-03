<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UniqueSubnetPerRouterTest extends TestCase
{
    protected AvailabilityZone $availabilityZone;
    protected Network $network;
    protected Network $network2;
    protected Region $region;
    protected Router $router;
    protected Router $router2;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = Region::factory()->create([
            'name' => 'testregion',
        ]);
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ]);
        $this->router2 = Router::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ]);
        $this->network = Network::factory()->create([
            'router_id' => $this->router->id,
            'subnet' => '10.0.0.1/30',
        ]);
        $this->network2 = Network::factory()->create([
            'router_id' => $this->router->id,
            'subnet' => '10.0.0.2/30',
        ]);
    }

    public function testOverlapDetectionWorks()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->id,
                'subnet' => '10.0.0.1/22'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'title' => 'Validation Error',
            'detail' => 'The subnet must not overlap an existing CIDR range',
        ])->assertStatus(422);
    }

    public function testSuccessfulCreation()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router2->id,
                'subnet' => '10.0.0.1/22'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);
    }

    public function testPatchIsSuccessful()
    {
        $response = $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router2->id,
                'subnet' => '10.0.0.1/22'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);

        $networkId = (json_decode($response->getContent()))->data->id;

        // update the record using the same data. Before the fix the same subnet for the same
        // record would result in an overlapping subnet error.
        $this->patch(
            '/v2/networks/'.$networkId,
            [
                'name' => 'Updated Network',
                'router_id' => $this->router2->id,
                'subnet' => '10.0.0.1/22'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);
    }

}
