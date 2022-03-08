<?php

namespace Tests\Unit\Models;

use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use Tests\TestCase;

class NicTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testIpaddressAttributeReturnsModelAttribute()
    {
        $nic = Nic::factory()->create([
            'id' => 'nic-test',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'instance_id' => $this->instanceModel()->id,
            'network_id' => $this->network()->id,
            'ip_address' => '1.1.1.1',
        ]);

        $this->assertEquals('1.1.1.1', $nic->ip_address);
    }

    public function testIpaddressAttributeReturnsDhcpIpAddress()
    {
        $ipAddress = IpAddress::factory()->create();

        $this->nic()->ipAddresses()->sync($ipAddress);

        $this->assertDatabaseMissing('nics', [
            'id' => $this->nic()->id,
            'ip_address' => '1.1.1.1'
        ],
        'ecloud');

        $this->assertEquals('1.1.1.1', $this->nic()->ip_address);
    }

    public function testIpaddressAttributeReturnsNullWhenNoValueInNicsTableAndNoRecordInIpAddressesTable()
    {
        $this->nic();

        $this->assertNull($this->nic()->ip_address);
    }
}
