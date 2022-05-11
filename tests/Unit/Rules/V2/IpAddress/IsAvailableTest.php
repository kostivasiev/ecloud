<?php

namespace Tests\Unit\Rules\V2\IpAddress;

use App\Models\V2\IpAddress;
use App\Rules\V2\IpAddress\IsAvailable;
use Tests\TestCase;

class IsAvailableTest extends TestCase
{
    public function testNotAvailableFails() {
        IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'ip_address' => '10.0.0.5'
        ]);

        $rule = new IsAvailable($this->network()->id);

        $result = $rule->passes('network_id', "10.0.0.5");

        $this->assertFalse($result);
    }

    public function testAvailablePasses() {
        $rule = new IsAvailable($this->network()->id);

        $result = $rule->passes('network_id', '10.0.0.5');

        $this->assertTrue($result);
    }
}