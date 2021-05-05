<?php

namespace Tests\V2\FirewallRulePort;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

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

        $this->firewallRule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->firewallPolicy()->id,
        ]);

        $this->firewallRulePort = factory(FirewallRulePort::class)->create([
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
        )
            ->seeJson([
                'firewall_rule_id' => $this->firewallRulePort->firewall_rule_id,
                'protocol' => $this->firewallRulePort->protocol,
                'source' => $this->firewallRulePort->source,
                'destination' => $this->firewallRulePort->destination
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'firewall_rule_id' => $this->firewallRulePort->firewall_rule_id,
                'protocol' => $this->firewallRulePort->protocol,
                'source' => $this->firewallRulePort->source,
                'destination' => $this->firewallRulePort->destination
            ])
            ->assertResponseStatus(200);
    }
}
