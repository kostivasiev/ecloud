<?php

namespace Tests\Unit\Rules\V2\FirewallRulePort;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Rules\V2\FirewallRulePort\UniquePortListRule;
use App\Rules\V2\FirewallRulePort\UniquePortRangeRule;
use App\Rules\V2\FirewallRulePort\UniquePortRule;
use Tests\TestCase;

class UniquePortListRuleTest extends TestCase
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

        app()->bind(UniquePortRangeRule::class, function () use ($firewallRule, $firewallRulePort) {
            $rule = \Mockery::mock(UniquePortRangeRule::class)->makePartial();
            $rule->model = new FirewallRulePort();
            $rule->parentKeyColumn = 'firewall_rule_id';
            $rule->parentId = $firewallRule->id;
            $rule->source = $firewallRulePort->source;
            $rule->destination = $firewallRulePort->destination;
            $rule->protocol = $firewallRulePort->protocol;
            return $rule;
        });

        app()->bind(UniquePortRule::class, function () use ($firewallRule, $firewallRulePort) {
            $rule = \Mockery::mock(UniquePortRule::class)->makePartial();
            $rule->model = new FirewallRulePort();
            $rule->parentKeyColumn = 'firewall_rule_id';
            $rule->parentId = $firewallRule->id;
            $rule->source = $firewallRulePort->source;
            $rule->destination = $firewallRulePort->destination;
            $rule->protocol = $firewallRulePort->protocol;
            return $rule;
        });

        $this->rule = \Mockery::mock(UniquePortListRule::class)->makePartial();
        $this->rule->class = FirewallRulePort::class;
    }

    public function testValidValuesPass()
    {
        $this->assertTrue($this->rule->passes('source', '80-90,210,3306'));
        $this->assertTrue($this->rule->passes('destination', '80,601-611,3306'));
    }

    public function testInvalidValuesFail()
    {
        $this->assertFalse($this->rule->passes('source', '80,110-120,8080'));
        $this->assertFalse($this->rule->passes('destination', '80,501-511,3306'));
    }
}
