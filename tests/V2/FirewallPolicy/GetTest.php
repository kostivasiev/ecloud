<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\FirewallRule;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected FirewallRule $firewallRule;

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
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/firewall-policies',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallPolicy()->id,
                'name' => $this->firewallPolicy()->name,
                'sequence' => $this->firewallPolicy()->sequence,
                'router_id' => $this->router()->id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/firewall-policies/' . $this->firewallPolicy()->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallPolicy()->id,
                'name' => $this->firewallPolicy()->name,
                'sequence' => $this->firewallPolicy()->sequence,
                'router_id' => $this->router()->id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetFirewallPolicyFirewallRules()
    {
        $this->get(
            '/v2/firewall-policies/' . $this->firewallPolicy()->id . '/firewall-rules',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallRule->id,
                'firewall_policy_id' => $this->firewallPolicy()->id
            ])
            ->assertResponseStatus(200);
    }
}
