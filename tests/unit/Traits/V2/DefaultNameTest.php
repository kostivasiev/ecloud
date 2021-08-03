<?php

namespace Tests\unit\Traits\V2;

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
        $this->instance()->name = '';

        $mock = $this->getMockForTrait(DefaultName::class);
        $mock->setDefaultName($this->instance());

        $this->assertEquals($this->instance()->id, $this->instance()->name);
    }

    public function testAvailabilityZoneNotOverridden()
    {
        $this->instance()->name = 'oldname';

        $mock = $this->getMockForTrait(DefaultName::class);
        $mock->setDefaultName($this->instance());

        $this->assertEquals('oldname', $this->instance()->name);
    }
}
