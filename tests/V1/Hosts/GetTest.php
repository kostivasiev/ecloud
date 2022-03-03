<?php

namespace Tests\V1\Hosts;

use App\Models\V1\Host;
use App\Models\V1\Solution;
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
        Solution::factory(1)->create();

        $count = 2;
        Host::factory($count)->create();

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
//    public function testValidItem()
//    {
//        // mock services
//        $service = Mockery::mock('App\Services\Kingpin\V1\KingpinService');
//        $service->shouldReceive('getHostByMac')->once()->andReturn((object) [
//            'uuid' => 'HostSystem-host-01',
//            'name' => '172.1.1.2',
//            'macAddress' => '12:3a:b4:56:c7:e8',
//            'powerStatus' => 'poweredOn',
//            'networkStatus' => 'connected',
//            'vms' => [],
//            'stats' => null,
//        ]);
//        $this->app->instance('App\Services\Kingpin\V1\KingpinService', $service);
//
//
//        // populate db
//        factory(Pod::class, 1)->create();
//        factory(Solution::class, 1)->create();
//
//        factory(Host::class, 1)->create([
//            'ucs_node_id' => 123,
//        ]);
//
//
//        // call api
//        $this->get('/v1/hosts/123', [
//            'X-consumer-custom-id' => '1-1',
//            'X-consumer-groups' => 'ecloud.read',
//        ]);
//
//
//        echo $this->response->getContent();
//        exit(PHP_EOL);
//
//        $this->assertResponseStatus(200);
//    }

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
