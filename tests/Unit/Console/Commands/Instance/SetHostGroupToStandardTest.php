<?php

namespace Tests\Unit\Console\Commands\Instance;

use App\Console\Commands\Instance\SetHostGroupToStandard;
use App\Models\V2\HostGroup;
use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class SetHostGroupToStandardTest extends TestCase
{
    public $command;

    public function setUp(): void
    {
        parent::setUp();
        $this->instanceModel();
        $this->command = \Mockery::mock(SetHostGroupToStandard::class)->makePartial();
    }

    public function testResults()
    {
        $this->kingpinServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['hostGroupID' => 1001]));
            });

        $this->command->allows('info')->with(\Mockery::capture($message))->andReturnTrue();

        $this->assertNull($this->instanceModel()->host_group_id);

        $this->command->handle();

        $this->instanceModel()->refresh();
        $this->assertNotNull($this->instanceModel()->host_group_id);
    }
}
