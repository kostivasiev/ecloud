<?php

namespace Tests\Unit\Rules\V2;

use App\Rules\V2\ValidateIpTypesAreConsistent;
use PHPUnit\Framework\TestCase;

class ValidateIpTypesAreConsistentTest extends TestCase
{
    public $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = \Mockery::mock(ValidateIpTypesAreConsistent::class)->makePartial();
    }

    public function testIPv4IsConsistent()
    {
        $this->rule->otherIpValue = '192.168.10.12/24';
        $this->assertTrue($this->rule->passes('source', '10.1.1.0/24'));
    }

    public function testIpv6IsConsistent()
    {
        $this->rule->otherIpValue = '78a6:9d0e:1937:ce40:312c:6718:0f98:400f/24';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f/24'));
    }

    public function testIpMismatchesFail()
    {
        $this->rule->otherIpValue = '192.168.10.12/24';
        $this->assertFalse($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f/24'));

        $this->rule->otherIpValue = '78a6:9d0e:1937:ce40:312c:6718:0f98:400f/24';
        $this->assertFalse($this->rule->passes('source', '10.1.1.0/24'));
    }

    public function testSucceedsIfEitherValueIsAny()
    {
        $this->rule->otherIpValue = 'ANY';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f/24'));

        $this->rule->otherIpValue = '192.168.10.12/24';
        $this->assertTrue($this->rule->passes('source', 'ANY'));
    }
}
