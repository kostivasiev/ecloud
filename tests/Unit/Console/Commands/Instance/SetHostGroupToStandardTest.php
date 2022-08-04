<?php

namespace Tests\Unit\Console\Commands\Instance;

use App\Console\Commands\Instance\SetHostGroupToStandard;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SetHostGroupToStandardTest extends TestCase
{
    public $command;

    public function setUp(): void
    {
        parent::setUp();
        $this->instanceModel();
        $this->availabilityZone()
            ->setAttribute('resource_tier_id', $this->resourceTier()->id)
            ->saveQuietly();

        $this->kingpinServiceMock()
            ->allows('post')
            ->withSomeOfArgs(KingpinService::SHARED_HOST_GROUP_CAPACITY)
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'hostGroupId' => $this->hostGroup()->id,
                        'cpuUsage' => 90,
                        'cpuUsedMHz' => 90,
                        'cpuCapacityMHz' => 100,
                        'ramUsage' => 90,
                        'ramUsedMB' => 900,
                        'ramCapacityMB' => 1000,
                    ]
                ]));
            });

        $this->command = \Mockery::mock(SetHostGroupToStandard::class)->makePartial();
        $this->command->allows('option')->with('test-run')->andReturnFalse();
        $this->command->allows('info')->andReturnTrue();
    }

    public function testResults()
    {
        Config::set('host-group-map.az-test', []);
        $this->instanceModel()
            ->setAttribute('host_group_id', null)
            ->saveQuietly();
        $this->assertNull($this->instanceModel()->host_group_id);

        $this->command->handle();

        $this->instanceModel()->refresh();
        $this->assertEquals($this->hostGroup()->id, $this->instanceModel()->host_group_id);
    }
}
