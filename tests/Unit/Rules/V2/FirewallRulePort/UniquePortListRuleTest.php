<?php

namespace Tests\Unit\Rules\V2\FirewallRulePort;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Rules\V2\FirewallRulePort\UniquePortListRule;
use Tests\TestCase;

class UniquePortListRuleTest extends TestCase
{
    public $rule;
    public FirewallRule $firewallRule;

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

        FirewallRulePort::withoutEvents(function () {
            return FirewallRulePort::factory()
                ->for($this->firewallRule)
                ->create([
                    'id' => 'fwrp-test',
                    'protocol' => 'TCP',
                    'source' => '100',
                    'destination' => '500',
                ]);
        });

        $this->rule = \Mockery::mock(UniquePortListRule::class)->makePartial();
        $this->rule->class = FirewallRulePort::class;
        $this->rule->model = new $this->rule->class;
        $this->rule->parentKeyColumn = 'firewall_rule_id';
        $this->rule->parentId = $this->firewallRule->id;
        $this->rule->protocol = 'TCP';
    }

    public function testValidValuesPass()
    {
        $this->assertTrue($this->rule->passes('source', '80-90,210,3306'));
        $this->assertTrue($this->rule->passes('destination', '80,601-611,3306'));
    }

    public function testInvalidValuesFail()
    {
        $this->assertFalse($this->rule->passes('source', '100,110-120,8080'));
        $this->assertFalse($this->rule->passes('destination', '500,501-511,3306'));
    }
}
