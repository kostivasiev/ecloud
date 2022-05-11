<?php

namespace Tests\Unit\Rules\V2\IpAddress;

use App\Models\V2\IpAddress;
use App\Rules\V2\IpAddress\IsClusterType;
use Tests\TestCase;

class IsClusterTypeTest extends TestCase
{
    public function testInvalidIpTypeFails() {
        $rule = new IsClusterType;

        $ipAddress = IpAddress::factory()->create([
                                                      'type' => IpAddress::TYPE_DHCP,
        ]);

        $result = $rule->passes('ip_address_id', $ipAddress->id);

        $this->assertFalse($result);
    }

    public function testValidIpTypePasses() {
        $rule = new IsClusterType;

        $ipAddress = IpAddress::factory()->create([
            'type' => IpAddress::TYPE_CLUSTER,
        ]);

        $result = $rule->passes('ip_address_id', $ipAddress->id);

        $this->assertTrue($result);
    }
}