<?php

namespace Tests\V2\FirewallRulePort;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected FirewallRule $firewallRule;
    protected FirewallRulePort $firewallRulePort;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('patch')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });

        $this->firewallRule = FirewallRule::factory()->create([
            'firewall_policy_id' => $this->firewallPolicy()->id,
        ]);

        $this->firewallRulePort = FirewallRulePort::factory()->create([
            'firewall_rule_id' => $this->firewallRule->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/firewall-rule-ports',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'firewall_rule_id' => $this->firewallRulePort->firewall_rule_id,
            'protocol' => $this->firewallRulePort->protocol,
            'source' => $this->firewallRulePort->source,
            'destination' => $this->firewallRulePort->destination
        ])->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'firewall_rule_id' => $this->firewallRulePort->firewall_rule_id,
            'protocol' => $this->firewallRulePort->protocol,
            'source' => $this->firewallRulePort->source,
            'destination' => $this->firewallRulePort->destination
        ])->assertStatus(200);
    }

    public function testGetHiddenNotAdminFails()
    {
        $this->router()->setAttribute('is_management', true)->save();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->get('/v2/firewall-rule-ports/' . $this->firewallRulePort->id)
            ->assertStatus(404);
    }

    public function testGetHiddenAdminPasses()
    {
        $this->router()->setAttribute('is_management', true)->save();

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/firewall-rule-ports/' . $this->firewallRulePort->id)->assertStatus(200);
    }
}
