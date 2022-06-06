<?php

namespace Tests\Unit\Rules\V2\FirewallRulePort;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Rules\V2\FirewallRulePort\UniquePortRule;
use Tests\TestCase;

class UniquePortRuleTest extends TestCase
{
    public $rule;
    public FirewallRule $firewallRule;
    public FirewallRulePort $firewallRulePort;

    public function setUp(): void
    {
        parent::setUp();

        $this->firewallRule = FirewallRule::withoutEvents(function () {
            return FirewallRule::factory()
                ->for($this->firewallPolicy())
                ->create([
                    'id' => 'fwr-test',
                ]);
        });

        $this->firewallRulePort = FirewallRulePort::withoutEvents(function () {
            return FirewallRulePort::factory()
                ->for($this->firewallRule)
                ->create([
                    'id' => 'fwrp-test',
                    'protocol' => 'TCP',
                    'source' => 80,
                    'destination' => 555,
                ]);
        });

        $this->rule = \Mockery::mock(UniquePortRule::class)->makePartial();
        $this->rule->model = new FirewallRulePort();
        $this->rule->parentKeyColumn = 'firewall_rule_id';
        $this->rule->parentId = $this->firewallRule->id;
        $this->rule->source = $this->firewallRulePort->source;
        $this->rule->destination = $this->firewallRulePort->destination;
        $this->rule->protocol = $this->firewallRulePort->protocol;
    }

    public function testValidValuesPass()
    {
        $this->assertTrue($this->rule->passes('source', 555));
    }

    public function testInvalidValuesFail()
    {
        $this->assertFalse($this->rule->passes('source', 80));
    }

    public function testAnyPort()
    {
        $firewallRule = FirewallRule::withoutEvents(function () {
            return FirewallRule::factory()
                ->for($this->firewallPolicy())
                ->create([
                    'id' => 'fwr-test2',
                ]);
        });

        $firewallRulePort = FirewallRulePort::withoutEvents(function () use ($firewallRule) {
            return FirewallRulePort::factory()
                ->for($firewallRule)
                ->create([
                    'id' => 'fwrp-test-2',
                    'protocol' => 'TCP',
                    'source' => 'ANY',
                    'destination' => 555,
                ]);
        });

        $this->rule = \Mockery::mock(UniquePortRule::class)->makePartial();
        $this->rule->model = new FirewallRulePort();
        $this->rule->parentKeyColumn = 'firewall_rule_id';
        $this->rule->parentId = $firewallRule->id;
        $this->rule->source = $firewallRulePort->source;
        $this->rule->destination = $firewallRulePort->destination;
        $this->rule->protocol = $firewallRulePort->protocol;

        $this->assertTrue($this->rule->passes('source', 80));
    }
}
