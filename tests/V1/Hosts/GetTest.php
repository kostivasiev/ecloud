<?php

namespace Tests\V1\Hosts;

use App\Models\V1\Host;
use App\Models\V1\HostSpecification;
use App\Models\V1\Pod;
use App\Models\V1\Solution;
use App\Services\Kingpin\V1\KingpinService;
use Tests\V1\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test for valid collection
     * @return void
     */
    public function testValidCollection()
    {
        $solution = Solution::factory()->create();
        $specification = HostSpecification::factory()->create([
            'ucs_specification_active' => 'Yes',
        ]);

        $count = 2;
        Host::factory($count)->create([
            'ucs_node_ucs_reseller_id' => $solution->getKey(),
            'ucs_node_status' => 'Active',
            'ucs_node_specification_id' => $specification->getKey(),
        ]);

        $this->get('/v1/hosts', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200)->assertJsonFragment([
            'total' => $count,
            'count' => $count,
        ]);
    }

    /**
     * Test for valid item
     * @return void
     */
    public function testValidItem()
    {
        app()->bind(KingpinService::class, function () {
            $service = \Mockery::mock('App\Services\Kingpin\V1\KingpinService');
            $service->allows('getHostByMac')
                ->once()
                ->andReturn((object) [
                'uuid' => 'HostSystem-host-01',
                'name' => '172.1.1.2',
                'macAddress' => '12:3a:b4:56:c7:e8',
                'powerStatus' => 'poweredOn',
                'networkStatus' => 'connected',
                'vms' => [],
                'stats' => null,
            ]);
            return $service;
        });

        // populate db
        $pod = Pod::factory()->create();
        Solution::factory()->create();
        $hostSpecification = HostSpecification::factory()->create();

        $host = Host::factory()->create([
            'ucs_node_status' => 'Active',
            'ucs_node_ucs_reseller_id' => 1,
            'ucs_node_specification_id' => $hostSpecification->getKey(),
        ]);

        // call api
        $response = $this->get(
            sprintf('/v1/hosts/%d', $host->getKey()),
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'pod_id' => $pod->getKey(),
            'name' => $hostSpecification->ucs_specification_friendly_name,
        ])->assertStatus(200);
    }

    /**
     * Test for invalid item
     * @return void
     */
    public function testInvalidItem()
    {
        $this->get('/v1/hosts/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }
}
