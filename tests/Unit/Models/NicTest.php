<?php

namespace Tests\Unit\Models;

use App\Models\V2\IpAddress;
use Tests\TestCase;

class NicTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testIpaddressAttributeReturnsDhcpIpAddress()
    {
        $ipAddress = IpAddress::factory()->create();
        $this->nic()->ipAddresses()->sync($ipAddress);

        $this->assertDatabaseMissing(
            'nics',
            [
                'id' => $this->nic()->id,
                'ip_address' => '1.1.1.1'
            ],
            'ecloud'
        );

        $this->assertEquals('1.1.1.1', $this->nic()->ip_address);
    }

    public function testIpaddressAttributeReturnsNullWhenNoRecordInIpAddressesTable()
    {
        $this->nic();

        $this->assertNull($this->nic()->ip_address);
    }
}
