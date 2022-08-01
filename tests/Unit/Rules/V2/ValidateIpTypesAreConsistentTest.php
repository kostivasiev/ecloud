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
        // IP
        $this->rule->otherIpValue = '192.168.10.12';
        $this->assertTrue($this->rule->passes('source', '10.1.1.0'));

        // Subnet
        $this->rule->otherIpValue = '192.168.10.12/24';
        $this->assertTrue($this->rule->passes('source', '10.1.1.0/24'));

        // Range
        $this->rule->otherIpValue = '192.168.10.12-192.168.10.122';
        $this->assertTrue($this->rule->passes('source', '10.1.1.0-10.1.1.100'));

        // List
        $this->rule->otherIpValue = '192.168.10.12,192.168.10.122';
        $this->assertTrue($this->rule->passes('source', '10.1.1.0,10.1.1.100/24,10.1.1.0-20.1.1.30'));
    }

    public function testIpv6IsConsistent()
    {
        // IP
        $this->rule->otherIpValue = '78a6:9d0e:1937:ce40:312c:6718:0f98:400f';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f'));

        // Subnet
        $this->rule->otherIpValue = '78a6:9d0e:1937:ce40:312c:6718:0f98:400f/24';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f/24'));

        // Range
        $this->rule->otherIpValue = '78a6:9d0e:1937:ce40:312c:6718:0f98:400f-78a6:9d0e:1937:ce40:312c:6718:0f98:ffff';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f-3492:e2e1:3616:951f:ddfb:295f:d6ba:ffff'));

        // List
        $this->rule->otherIpValue = '4911:42f7:1f9e:2763:aabe:ea3c:ef9b:58f4,78a6:9d0e:1937:ce40:312c:6718:0f98:400f-78a6:9d0e:1937:ce40:312c:6718:0f98:ffff';
        $this->assertTrue($this->rule->passes('source', '48a7:3da2:9293:9701:7dfb:2c9e:dd49:6b7b/24,123d:bede:22f6:741c:ab0f:a2da:e60f:7b84,3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f-3492:e2e1:3616:951f:ddfb:295f:d6ba:ffff'));
    }

    public function testIpMismatchesFail()
    {
        // IP
        $this->rule->otherIpValue = '192.168.10.12';
        $this->assertFalse($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f/24'));

        $this->rule->otherIpValue = '78a6:9d0e:1937:ce40:312c:6718:0f98:400f';
        $this->assertFalse($this->rule->passes('source', '10.1.1.0/24'));

        // Subnet
        $this->rule->otherIpValue = '192.168.10.12/24';
        $this->assertFalse($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f/24'));

        $this->rule->otherIpValue = '78a6:9d0e:1937:ce40:312c:6718:0f98:400f/24';
        $this->assertFalse($this->rule->passes('source', '10.1.1.0/24'));

        // Range
        $this->rule->otherIpValue = '192.168.10.12-192.168.10.122';
        $this->assertFalse($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f/24'));

        $this->rule->otherIpValue = '78a6:9d0e:1937:ce40:312c:6718:0f98:400f-78a6:9d0e:1937:ce40:312c:6718:0f98:ffff';
        $this->assertFalse($this->rule->passes('source', '10.1.1.0/24'));

        // List
        $this->rule->otherIpValue = '48a7:3da2:9293:9701:7dfb:2c9e:dd49:6b7b/24,123d:bede:22f6:741c:ab0f:a2da:e60f:7b84,3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f-3492:e2e1:3616:951f:ddfb:295f:d6ba:ffff';
        $this->assertFalse($this->rule->passes('source', '10.1.1.0/24'));

        $this->rule->otherIpValue = '192.168.10.12,192.168.10.122';
        $this->assertFalse($this->rule->passes('source', '48a7:3da2:9293:9701:7dfb:2c9e:dd49:6b7b/24,123d:bede:22f6:741c:ab0f:a2da:e60f:7b84,3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f-3492:e2e1:3616:951f:ddfb:295f:d6ba:ffff'));
    }

    public function testSucceedsIfEitherValueIsAny()
    {
        // Subnet
        $this->rule->otherIpValue = 'ANY';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f'));

        $this->rule->otherIpValue = '192.168.10.12';
        $this->assertTrue($this->rule->passes('source', 'ANY'));

        // Subnet
        $this->rule->otherIpValue = 'ANY';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f/24'));

        $this->rule->otherIpValue = '192.168.10.12/24';
        $this->assertTrue($this->rule->passes('source', 'ANY'));

        // Range
        $this->rule->otherIpValue = 'ANY';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f-3492:e2e1:3616:951f:ddfb:295f:d6ba:ffff'));

        $this->rule->otherIpValue = '192.168.10.12-192.168.10.121';
        $this->assertTrue($this->rule->passes('source', 'ANY'));

        // List
        $this->rule->otherIpValue = 'ANY';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f,3492:e2e1:3616:951f:ddfb:295f:d6ba:ffff'));

        $this->rule->otherIpValue = '192.168.10.12,192.168.10.121';
        $this->assertTrue($this->rule->passes('source', 'ANY'));
    }

    public function testItemsInAListAreConsistent()
    {
        $this->rule->otherIpValue = 'ANY';
        $this->assertFalse($this->rule->passes('source', '192.168.10.12,3492:e2e1:3616:951f:ddfb:295f:d6ba:ffff'));

        $this->rule->otherIpValue = 'ANY';
        $this->assertFalse($this->rule->passes('source', 'INVALID,3492:e2e1:3616:951f:ddfb:295f:d6ba:ffff'));

        $this->rule->otherIpValue = 'ANY';
        $this->assertTrue($this->rule->passes('source', '3492:e2e1:3616:951f:ddfb:295f:d6ba:1c0f,3492:e2e1:3616:951f:ddfb:295f:d6ba:ffff'));

        $this->rule->otherIpValue = 'ANY';
        $this->assertTrue($this->rule->passes('source', '192.168.10.12,192.168.10.13,192.168.10.14'));
    }
}
