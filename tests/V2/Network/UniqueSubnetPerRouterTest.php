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
    use DatabaseMigrations;

    protected AvailabilityZone $availabilityZone;
    protected Network $network;
    protected Network $network2;
    protected Region $region;
    protected Router $router;
    protected Router $router2;
    protected Vpc $vpc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create([
            'name' => 'testregion',
        ]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ]);
        $this->router2 = factory(Router::class)->create([
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone->id,
        ]);
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router->id,
            'subnet' => '10.0.0.1/30',
        ]);
        $this->network2 = factory(Network::class)->create([
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
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The subnet must not overlap an existing CIDR range',
        ])->assertResponseStatus(422);
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
        )->assertResponseStatus(201);
    }

    public function testPatchIsSuccessful()
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
        )->assertResponseStatus(201);

        $networkId = (json_decode($this->response->getContent()))->data->id;

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
        )->assertResponseStatus(200);
    }

}
