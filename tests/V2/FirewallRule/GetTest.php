<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected FirewallRule $firewallRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        // TODO - Replace with real mock
        $this->nsxServiceMock()->allows('patch')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        // TODO - Replace with real mock
        $this->nsxServiceMock()->allows('get')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });

        $this->firewallRule = FirewallRule::factory()
            ->for($this->firewallPolicy())
            ->create();

        $this->firewallRulePort = FirewallRulePort::factory()
            ->for($this->firewallRule)
            ->create();
    }

    public function testGetCollection()
    {
        $this->asAdmin()
            ->get(
                '/v2/firewall-rules'
            )->assertJsonFragment([
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => $this->firewallRule->source,
                'destination' => $this->firewallRule->destination,
                'action' => $this->firewallRule->action,
                'direction' => $this->firewallRule->direction,
                'enabled' => $this->firewallRule->enabled,
                'id' => $this->firewallRule->id,
                'name' => $this->firewallRule->name,
                'sequence' => $this->firewallRule->sequence,
            ])->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->asAdmin()
            ->get(
                '/v2/firewall-rules/' . $this->firewallRule->id
            )->assertJsonFragment([
                'id' => $this->firewallRule->id,
                'name' => $this->firewallRule->name,
                'sequence' => $this->firewallRule->sequence,
            ])->assertStatus(200);
    }

    public function testGetPortsCollection()
    {
        $this->asAdmin()
            ->get(
                '/v2/firewall-rules/' . $this->firewallRule->id . '/ports'
            )->assertJsonFragment([
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ])->assertStatus(200);
    }

    public function testGetHiddenNotAdminFails()
    {
        $this->router()->setAttribute('is_management', true)->save();
        $this->asUser()
            ->get('/v2/firewall-rules/' . $this->firewallRule->id)
            ->assertStatus(404);
    }

    public function testGetHiddenAdminPasses()
    {
        $this->router()->setAttribute('is_management', true)->save();
        $this->asAdmin()
            ->get('/v2/firewall-rules/' . $this->firewallRule->id)
            ->assertStatus(200);
    }
}
