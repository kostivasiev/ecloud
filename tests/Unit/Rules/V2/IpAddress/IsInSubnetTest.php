<?php

namespace Tests\Unit\Rules\V2\IpAddress;

use App\Rules\V2\IpAddress\IsInSubnet;
use Tests\TestCase;

class IsInSubnetTest extends TestCase
{
    public function testInvalidIpFails() {
        $rule = new IsInSubnet($this->network()->id);

        $result = $rule->passes('network_id', "1.1.1.1");

        $this->assertFalse($result);
    }

    public function testValidIpPasses() {
        $rule = new IsInSubnet($this->network()->id);

        $result = $rule->passes('network_id', '10.0.0.5');

        $this->assertTrue($result);
    }
}