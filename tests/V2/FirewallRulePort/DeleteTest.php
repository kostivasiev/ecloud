<?php

namespace Tests\V2\FirewallRulePort;

use App\Events\V2\Task\Created;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    protected FirewallRule $firewallRule;
    protected FirewallRulePort $firewallRulePort;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        $this->firewallRule = FirewallRule::factory()->create([
            'firewall_policy_id' => $this->firewallPolicy()->id,
        ]);

        $this->firewallRulePort = FirewallRulePort::factory()->create([
            'firewall_rule_id' => $this->firewallRule->id,
        ]);

        Event::fake([Created::class]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete('v2/firewall-rule-ports/' . $this->firewallRulePort->id, [], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);
        $this->assertNotFalse(FirewallRulePort::find($this->firewallRulePort->id));

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }
}
