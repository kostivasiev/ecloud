<?php

namespace Tests\Unit\Traits\V2;

use App\Traits\V2\DefaultName;
use Tests\TestCase;

class DefaultNameTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSetsNameToIDWithNoneSet()
    {
        $this->instanceModel()->name = '';

        $mock = $this->getMockForTrait(DefaultName::class);
        $mock->setDefaultName($this->instanceModel());

        $this->assertEquals($this->instanceModel()->id, $this->instanceModel()->name);
    }

    public function testAvailabilityZoneNotOverridden()
    {
        $this->instanceModel()->name = 'oldname';

        $mock = $this->getMockForTrait(DefaultName::class);
        $mock->setDefaultName($this->instanceModel());

        $this->assertEquals('oldname', $this->instanceModel()->name);
    }
}
