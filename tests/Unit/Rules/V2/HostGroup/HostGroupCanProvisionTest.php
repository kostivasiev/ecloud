<?php

namespace Tests\Unit\Rules\V2\HostGroup;

use App\Rules\V2\HostGroup\HostGroupCanProvision;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class HostGroupCanProvisionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testHostGroupCanProvisionPasses()
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

        $rule = new HostGroupCanProvision(1024);
        $this->assertTrue($rule->passes('host_group_id', $this->hostGroup()->id));
    }

    public function testHostGroupCanNotProvisionFails()
    {
        $this->kingpinServiceMock()
            ->allows('get')
            ->with(
                sprintf(KingpinService::PRIVATE_HOST_GROUP_CAPACITY, $this->vpc()->id, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupId' => $this->hostGroup()->id,
                    'cpuUsage' => 100,
                    'cpuUsedMHz' => 1000,
                    'cpuCapacityMHz' => 1000,
                    'ramUsage' => 100,
                    'ramUsedMB' => 1000,
                    'ramCapacityMB' => 1000,
                ]));
            });

        $rule = new HostGroupCanProvision(1024);
        $this->assertFalse($rule->passes('host_group_id', $this->hostGroup()->id));
    }
}
