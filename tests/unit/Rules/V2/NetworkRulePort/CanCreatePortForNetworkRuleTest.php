<?php

namespace Tests\unit\Rules\V2\NetworkRulePort;

use App\Models\V2\NetworkRule;
use App\Rules\V2\NetworkRulePort\CanCreatePortForNetworkRule;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CanCreatePortForNetworkRuleTest extends TestCase
{
    protected $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = new CanCreatePortForNetworkRule();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testNoTypePasses()
    {
        $rule = factory(NetworkRule::class)->create([
            'id' => 'nr-test',
            'network_policy_id' => $this->networkPolicy()->id,
        ]);
        $rule->save();

        $this->assertTrue($this->rule->passes('network_rule_id', $rule->id));
    }

    public function testDhcpTypeFails()
    {
        $rule = factory(NetworkRule::class)->create([
            'id' => 'nr-test',
            'network_policy_id' => $this->networkPolicy()->id,
            'type' => NetworkRule::TYPE_DHCP,
        ]);
        $rule->save();

        $this->assertFalse($this->rule->passes('network_rule_id', $rule->id));
    }

    public function testCatchallTypeFails()
    {
        $rule = factory(NetworkRule::class)->create([
            'id' => 'nr-test',
            'network_policy_id' => $this->networkPolicy()->id,
            'type' => NetworkRule::TYPE_CATCHALL,
        ]);
        $rule->save();

        $this->assertFalse($this->rule->passes('network_rule_id', $rule->id));
    }
}