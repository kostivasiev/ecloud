<?php

namespace Tests\Unit\Models;

use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use Tests\TestCase;

class NetworkTest extends TestCase
{
    protected Network $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->network = Network::factory()->create([
            "subnet" => "10.0.0.0/24"
        ]);
    }

    public function testIsReservedAddressReturnsTrueWithNetworkAddress()
    {
        $this->assertTrue($this->network->isReservedAddress("10.0.0.0"));
    }

    public function testIsReservedAddressReturnsTrueWithGatewayAddress()
    {
        $this->assertTrue($this->network->isReservedAddress("10.0.0.1"));
    }

    public function testIsReservedAddressReturnsTrueWithDhcpServerAddress()
    {
        $this->assertTrue($this->network->isReservedAddress("10.0.0.2"));
    }

    public function testIsReservedAddressReturnsTrueWithFutureReservedAddress()
    {
        $this->assertTrue($this->network->isReservedAddress("10.0.0.3"));
    }

    public function testIsReservedAddressReturnsTrueWithBroadcastAddress()
    {
        $this->assertTrue($this->network->isReservedAddress("10.0.0.255"));
    }

    public function testIsReservedAddressReturnsFalseWithUnreservedAddress()
    {
        $this->assertFalse($this->network->isReservedAddress("10.0.0.4"));
    }

    public function testGetNextAvailableIpReturnsNextUnreservedAddress()
    {
        $ipAddress = $this->network->getNextAvailableIp();
        $this->assertEquals("10.0.0.4", $ipAddress);
    }

    public function testGetNextAvailableIpSkipsAssignedIpAddresses()
    {
        IpAddress::factory()->create([
            "ip_address" => "10.0.0.4",
            "network_id" => $this->network->id,
        ]);

        $ipAddress = $this->network->getNextAvailableIp();
        $this->assertEquals("10.0.0.5", $ipAddress);
    }

    public function testGetNextAvailableIpSkipsDenyListIpAddresses()
    {
        $ipAddress = $this->network->getNextAvailableIp(["10.0.0.4"]);
        $this->assertEquals("10.0.0.5", $ipAddress);
    }

    public function testGetNextAvailableIpThrowsWhenNoAvailableIpAddresses()
    {
        $network = Network::factory()->create([
            "subnet" => "10.0.0.0/29"
        ]);
        IpAddress::factory()->create([
            "ip_address" => "10.0.0.4",
            "network_id" => $network->id,
        ]);
        IpAddress::factory()->create([
            "ip_address" => "10.0.0.5",
            "network_id" => $network->id,
        ]);
        IpAddress::factory()->create([
            "ip_address" => "10.0.0.6",
            "network_id" => $network->id,
        ]);

        $this->expectExceptionMessage('Insufficient available IP\'s in subnet on network ' . $network->id);
        $network->getNextAvailableIp();
    }
}
