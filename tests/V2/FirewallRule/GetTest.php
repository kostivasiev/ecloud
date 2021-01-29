<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected FirewallRule $firewallRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('patch')
            ->andReturn(
                new Response(200, [], ''),
            );

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturn(
                new Response(200, [], json_encode(['publish_status' => 'REALIZED']))
            );

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
            '/v2/firewall-rules',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => $this->firewallRule->source,
                'destination' => $this->firewallRule->destination,
                'action' => $this->firewallRule->action,
                'direction' => $this->firewallRule->direction,
                'enabled' => $this->firewallRule->enabled,
                'id' => $this->firewallRule->id,
                'name' => $this->firewallRule->name,
                'sequence' => (string)$this->firewallRule->sequence,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/firewall-rules/' . $this->firewallRule->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallRule->id,
                'name' => $this->firewallRule->name,
                'sequence' => (string)$this->firewallRule->sequence,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetPortsCollection()
    {
        $this->get(
            '/v2/firewall-rules/' . $this->firewallRule->id . '/ports',
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ])
            ->assertResponseStatus(200);
    }
}
