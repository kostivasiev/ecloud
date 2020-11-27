<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CidrValidationsTest extends TestCase
{
    use DatabaseMigrations;

    protected AvailabilityZone $availabilityZone;
    protected Network $network;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create([
            'name' => 'testregion',
        ]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey(),
        ]);
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
            'subnet' => '10.0.0.1/30',
        ]);
        factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
            'subnet' => '10.0.0.2/30',
        ]);
    }

    public function testCreateTooSmallSubnet()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->getKey(),
                'subnet' => '10.0.0.1/30'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The range in subnet is too small and must be greater than or equal to 30'
        ])->assertResponseStatus(422);
    }

    public function testUpdateTooSmallSubnet()
    {
        $this->patch(
            '/v2/networks/'.$this->network->getKey(),
            [
                'subnet' => '10.0.0.1/30'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The range in subnet is too small and must be greater than or equal to 30'
        ])->assertResponseStatus(422);
    }

    public function testCreateNetworkPublicCidr()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->getKey(),
                'subnet' => '208.97.176.25/24'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The subnet must be a private CIDR subnet'
        ])->assertResponseStatus(422);
    }

    public function testUpdateNetworkPublicCidr()
    {
        $this->patch(
            '/v2/networks/'.$this->network->getKey(),
            [
                'subnet' => '208.97.176.25/24'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The subnet must be a private CIDR subnet'
        ])->assertResponseStatus(422);
    }

    public function testOverlappingCidr()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->getKey(),
                'subnet' => '10.0.0.1/24'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The subnet must not overlap another CIDR subnet'
        ])->assertResponseStatus(422);
    }

    public function testUpdateWithOverlappingCidr()
    {
        $this->patch(
            '/v2/networks/'.$this->network->getKey(),
            [
                'subnet' => '10.0.0.2/24'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The subnet must not overlap another CIDR subnet'
        ])->assertResponseStatus(422);
    }

    public function testExistingCidr()
    {
        $this->post(
            '/v2/networks',
            [
                'name' => 'Manchester Network',
                'router_id' => $this->router->getKey(),
                'subnet' => '10.0.0.1/30'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The subnet is already assigned to another network'
        ])->assertResponseStatus(422);
    }

    public function testUpdateWithExistingCidr()
    {
        $this->patch(
            '/v2/networks/'.$this->network->getKey(),
            [
                'subnet' => '10.0.0.2/30'
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
                'X-Reseller-Id' => 1
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The subnet is already assigned to another network'
        ])->assertResponseStatus(422);
    }

}
