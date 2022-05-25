<?php

namespace Tests\Unit\Rules\V2;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Rules\V2\IsUniquePortOrRange;
use Tests\TestCase;

class IsUniquePortOrRangeTest extends TestCase
{
    public IsUniquePortOrRange $rule;
    public FirewallRule $firewallRule;

    public function setUp(): void
    {
        parent::setUp();
        $this->firewallRule = FirewallRule::withoutEvents(function () {
            return FirewallRule::factory()
                ->create([
                    'id' => 'fwr-aaaaaaaa',
                ]);
        });
        $this->rule = new IsUniquePortOrRange(FirewallRulePort::class, $this->firewallRule->id);
    }

    public function testUniquePortSucceeds()
    {
        $this->assertTrue($this->rule->passes('source', 443));
    }

    public function testUsedPortFails()
    {
        FirewallRulePort::withoutEvents(function () {
            FirewallRulePort::factory()
                ->for($this->firewallRule)
                ->create([
                    'id' => 'fwrp-aaaaaaaa',
                ]);
        });
        $this->assertFalse($this->rule->passes('source', 443));
    }

    public function testUniquePortRangeSucceeds()
    {
        $this->assertTrue($this->rule->passes('source', '400-499'));
    }

    public function testUsedPortRangeFails()
    {
        FirewallRulePort::withoutEvents(function () {
            FirewallRulePort::factory()
                ->for($this->firewallRule)
                ->create([
                    'id' => 'fwrp-aaaaaaaa',
                    'source' => '400-499',
                    'destination' => '100-119'
                ]);
        });
        $this->assertFalse($this->rule->passes('source', '400-499'));
        $this->assertFalse($this->rule->passes('destination', '100-119'));
    }
}
