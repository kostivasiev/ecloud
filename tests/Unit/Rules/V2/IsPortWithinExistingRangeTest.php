<?php

namespace Tests\Unit\Rules\V2;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Rules\V2\IsPortWithinExistingRange;
use Tests\TestCase;

class IsPortWithinExistingRangeTest extends TestCase
{
    public IsPortWithinExistingRange $rule;
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
        $this->rule = new IsPortWithinExistingRange(FirewallRulePort::class, $this->firewallRule->id);
    }

    public function testPortNotWithinExistingRangePasses()
    {
        $this->assertTrue($this->rule->passes('source', 433));
    }

    public function testPortWithinExistingRangeFails()
    {
        FirewallRulePort::withoutEvents(function () {
            FirewallRulePort::factory()
                ->for($this->firewallRule)
                ->create([
                    'id' => 'fwrp-aaaaaaaa',
                    'source' => '400-499',
                ]);
        });
        $this->assertFalse($this->rule->passes('source', 433));
    }

    public function testRangeWithinExistingRangeFails()
    {
        FirewallRulePort::withoutEvents(function () {
            FirewallRulePort::factory()
                ->for($this->firewallRule)
                ->create([
                    'id' => 'fwrp-aaaaaaaa',
                    'source' => '400-499',
                ]);
        });
        $this->assertFalse($this->rule->passes('source', '433-439'));
    }
}
