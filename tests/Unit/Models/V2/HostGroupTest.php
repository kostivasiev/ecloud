<?php

namespace Tests\Unit\Models\V2;

use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class HostGroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->kingpinServiceMock()
            ->allows('get')
            ->with(
                sprintf(KingpinService::GET_CAPACITY_URI, $this->vpc()->id, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupId' => $this->hostGroup()->id,
                    'cpuUsage' => 0,
                    'cpuUsedMHz' => 75,
                    'cpuCapacityMHz' => 100,
                    'ramUsage' => 0,
                    'ramUsedMB' => 92,
                    'ramCapacityMB' => 100,
                ]));
            });
    }

    public function testGetAvailableCapacity()
    {
        $capacity = $this->hostGroup()->getAvailableCapacity();
        $this->assertEquals(75, $capacity['cpu']['percentage']);
        $this->assertEquals(92, $capacity['ram']['percentage']);
    }
}
