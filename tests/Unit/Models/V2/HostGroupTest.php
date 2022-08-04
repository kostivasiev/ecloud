<?php

namespace Tests\Unit\Models\V2;

use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HostGroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Config::set('host-group-map.az-test', []);
        Config::set('hostgroup.capacity.threshold', 80);
    }

    public function testGetCapacityPrivateSuccess()
    {
        $this->kingpinServiceMock()
            ->allows('get')
            ->with(
                sprintf(KingpinService::PRIVATE_HOST_GROUP_CAPACITY, $this->vpc()->id, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupId' => $this->hostGroup()->id,
                    'cpuUsage' => 75,
                    'cpuUsedMHz' => 75,
                    'cpuCapacityMHz' => 100,
                    'ramUsage' => 92,
                    'ramUsedMB' => 92,
                    'ramCapacityMB' => 100,
                ]));
            });

        $capacity = $this->hostGroup()->getCapacity();
        $this->assertEquals(75, $capacity['cpu']['percentage']);
        $this->assertEquals(92, $capacity['ram']['percentage']);
    }

    public function testGetCapacitySharedSuccess()
    {
        $this->hostGroup()->setAttribute('vpc_id', null)->save();

        $this->kingpinServiceMock()
            ->expects('post')
            ->withArgs([
                KingpinService::SHARED_HOST_GROUP_CAPACITY,
                [
                    'json' => [
                        'hostGroupIds' => [
                            $this->hostGroup()->id
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'hostGroupId' => $this->hostGroup()->id,
                        'cpuUsage' => 75,
                        'cpuUsedMHz' => 75,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 92,
                        'ramUsedMB' => 92,
                        'ramCapacityMB' => 100,
                    ]
                ]));
            });

        $capacity = $this->hostGroup()->getCapacity();
        $this->assertEquals(75, $capacity['cpu']['percentage']);
        $this->assertEquals(92, $capacity['ram']['percentage']);
    }

    public function testHasCapacityForInstancePasses()
    {
        $this->kingpinServiceMock()
            ->allows('get')
            ->with(
                sprintf(KingpinService::PRIVATE_HOST_GROUP_CAPACITY, $this->vpc()->id, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupId' => $this->hostGroup()->id,
                    'cpuUsage' => 1,
                    'cpuUsedMHz' => 563,
                    'cpuCapacityMHz' => 43184,
                    'ramUsage' => 7,
                    'ramUsedMB' => 9740,
                    'ramCapacityMB' => 131001,
                ]));
            });
        // 'ram_capacity' => 1024
        $this->assertTrue($this->hostGroup()->canProvision($this->instanceModel()->ram_capacity));
    }

    public function testDoesNotHaveCapacityForInstanceFails()
    {
        $this->kingpinServiceMock()
            ->allows('get')
            ->with(
                sprintf(KingpinService::PRIVATE_HOST_GROUP_CAPACITY, $this->vpc()->id, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupId' => $this->hostGroup()->id,
                    'cpuUsage' => 1,
                    'cpuUsedMHz' => 563,
                    'cpuCapacityMHz' => 43184,
                    'ramUsage' => 100,
                    'ramUsedMB' => 131001,
                    'ramCapacityMB' => 131001,
                ]));
            });
        // 'ram_capacity' => 1024
        $this->assertFalse($this->hostGroup()->canProvision($this->instanceModel()->ram_capacity));
    }

    public function testHasNoComputeResourcesFails()
    {
        $this->kingpinServiceMock()
            ->allows('get')
            ->with(
                sprintf(KingpinService::PRIVATE_HOST_GROUP_CAPACITY, $this->vpc()->id, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupId' => $this->hostGroup()->id,
                    'cpuUsage' => 0,
                    'cpuUsedMHz' => 0,
                    'cpuCapacityMHz' => 0,
                    'ramUsage' => 0,
                    'ramUsedMB' => 0,
                    'ramCapacityMB' => 0,
                ]));
            });
        // 'ram_capacity' => 1024
        $this->assertFalse($this->hostGroup()->canProvision($this->instanceModel()->ram_capacity));
    }
}