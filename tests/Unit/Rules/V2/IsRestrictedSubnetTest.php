<?php

namespace Tests\Unit\Rules\V2;

use App\Rules\V2\IsRestrictedSubnet;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsRestrictedSubnetTest extends TestCase
{
    protected IsRestrictedSubnet $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = new IsRestrictedSubnet();
    }

    public function testRuleFailsOnSubnetMatchNonAdmin()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->assertFalse($this->rule->passes('subnet', '192.168.0.0/16'));
        $this->assertFalse($this->rule->passes('subnet', '10.255.255.0/24'));
    }

    public function testRulePassesOnSubnetMatchAdmin()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->assertTrue($this->rule->passes('subnet', '192.168.0.0/16'));
        $this->assertTrue($this->rule->passes('subnet', '10.255.255.0/24'));
    }
}