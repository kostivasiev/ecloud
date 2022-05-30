<?php

namespace Tests\Unit\Models;

use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use Tests\TestCase;

class IpAddressTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testReturnsResellerId()
    {
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
        ]);
        $resellerId = $ipAddress->getResellerId();

        $this->assertEquals($this->vpc()->reseller_id, $resellerId);
    }
}
