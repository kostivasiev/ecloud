<?php


namespace Tests\unit\Rules\V2\IpAddress;

use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use App\Rules\V2\IpAddress\IsSameNetworkAsNic;
use Tests\TestCase;

class IsSameNetworkAsNicTest extends TestCase
{
    public function testIpNotSameNetworkAsNicFails() {
        $network = factory(Network::class)->create([
            'id' => 'net-test-2',
            'router_id' => $this->router()->id
        ]);

        $ipAddress = IpAddress::factory()->create([
            'network_id' => $network->id,
            'ip_address' => '10.0.0.5'
        ]);

        $rule = new IsSameNetworkAsNic($this->nic()->id);

        $result = $rule->passes('ip_address_id', $ipAddress->id);

        $this->assertFalse($result);
    }

    public function testIpSameNetworkAsNicPasses() {
        $rule = new IsSameNetworkAsNic($this->nic()->id);

        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id
        ]);

        $result = $rule->passes('ip_address_id', $ipAddress->id);


        $this->assertTrue($result);
    }
}