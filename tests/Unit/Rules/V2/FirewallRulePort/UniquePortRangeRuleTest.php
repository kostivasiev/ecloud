<?php

namespace Tests\Unit\Rules\V2\FirewallRulePort;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Rules\V2\FirewallRulePort\UniquePortRangeRule;
use Tests\TestCase;

class UniquePortRangeRuleTest extends TestCase
{
    public $rule;

    public function setUp(): void
    {
        parent::setUp();

        $firewallRule = FirewallRule::withoutEvents(function () {
            return FirewallRule::factory()
                ->for($this->firewallPolicy())
                ->create([
                    'id' => 'fwr-test',
                ]);
        });

        $firewallRulePort = FirewallRulePort::withoutEvents(function () use ($firewallRule) {
            return FirewallRulePort::factory()
                ->for($firewallRule)
                ->create([
                    'id' => 'fwrp-test',
                    'protocol' => 'TCP',
                    'source' => '100-200',
                    'destination' => '500-600',
                ]);
        });

        $this->rule = \Mockery::mock(UniquePortRangeRule::class)->makePartial();
        $this->rule->model = new FirewallRulePort();
        $this->rule->parentKeyColumn = 'firewall_rule_id';
        $this->rule->parentId = $firewallRule->id;
        $this->rule->source = $firewallRulePort->source;
        $this->rule->destination = $firewallRulePort->destination;
        $this->rule->protocol = $firewallRulePort->protocol;
    }

    public function testValidValuesPass()
    {
        $this->assertTrue($this->rule->passes('source', '201-299'));
        $this->assertTrue($this->rule->passes('destination', '411-499'));
    }

    public function testInvalidValuesFail()
    {
        $this->assertFalse($this->rule->passes('destination', 550));
        $this->assertFalse($this->rule->passes('destination', '510-550'));
        $this->assertFalse($this->rule->passes('source', 105));
        $this->assertFalse($this->rule->passes('source', '180-240'));
    }
}
