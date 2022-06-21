<?php

namespace Tests\Unit\Console\Commands\Instance;

use App\Console\Commands\Instance\SetHostGroupToStandard;
use App\Models\V2\HostGroup;
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
        $this->command->allows('info')->with(\Mockery::capture($message))->andReturnTrue();

        $this->command->handle();

        dd(HostGroup::find(1001)->getAttributes());
    }
}
