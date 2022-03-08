<?php

namespace Tests\Unit\Jobs\NetworkPolicy;

use App\Jobs\NetworkPolicy\CreateDefaultNetworkRules;
use App\Models\V2\NetworkRule;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateDefaultNetworkRulesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {
        $this->networkPolicy();

        $this->assertEquals($this->networkPolicy()->networkRules()->count(), 0);

        Event::fake([JobFailed::class]);

        dispatch(new CreateDefaultNetworkRules($this->networkPolicy()));

        $this->assertEquals($this->networkPolicy()->networkRules()->count(), 3);

        $this->assertDatabaseHas('network_rules', [
            'name' => 'dhcp_ingress',
            'sequence' => 10000,
            'network_policy_id' => $this->networkPolicy()->id,
            'source' => '10.0.0.2',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true,
            'type' => NetworkRule::TYPE_DHCP
        ], 'ecloud');

        $this->assertDatabaseHas('network_rules', [
            'name' => 'dhcp_egress',
            'sequence' => 10001,
            'network_policy_id' => $this->networkPolicy()->id,
            'source' => 'ANY',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'OUT',
            'enabled' => true,
            'type' => NetworkRule::TYPE_DHCP
        ], 'ecloud');

        $this->assertDatabaseHas('network_rules', [
            'name' => NetworkRule::TYPE_CATCHALL,
            'sequence' => 20000,
            'network_policy_id' => $this->networkPolicy()->id,
            'source' => 'ANY',
            'destination' => 'ANY',
            'action' => 'REJECT',
            'direction' => 'IN_OUT',
            'enabled' => true,
            'type' => NetworkRule::TYPE_CATCHALL
        ], 'ecloud');

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCatchallRuleActionIsImplemented()
    {
        $this->networkPolicy();

        Event::fake([JobFailed::class]);

        dispatch(new CreateDefaultNetworkRules($this->networkPolicy(), ['catchall_rule_action' => 'ALLOW']));

        $this->assertDatabaseHas('network_rules', [
            'action' => 'ALLOW',
            'type' => NetworkRule::TYPE_CATCHALL
        ], 'ecloud');

        Event::assertNotDispatched(JobFailed::class);
    }
}
