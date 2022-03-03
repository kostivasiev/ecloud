<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\FirewallRule;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

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

        $this->firewallRule = FirewallRule::factory()->create([
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
        )->assertJsonFragment([
            'id' => $this->firewallPolicy()->id,
            'name' => $this->firewallPolicy()->name,
            'sequence' => $this->firewallPolicy()->sequence,
            'router_id' => $this->router()->id,
        ])->assertStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/firewall-policies/' . $this->firewallPolicy()->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'id' => $this->firewallPolicy()->id,
            'name' => $this->firewallPolicy()->name,
            'sequence' => $this->firewallPolicy()->sequence,
            'router_id' => $this->router()->id,
        ])->assertStatus(200);
    }

    public function testGetFirewallPolicyFirewallRules()
    {
        $this->get(
            '/v2/firewall-policies/' . $this->firewallPolicy()->id . '/firewall-rules',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'id' => $this->firewallRule->id,
            'firewall_policy_id' => $this->firewallPolicy()->id
        ])->assertStatus(200);
    }

    public function testGetHiddenNotAdminFails()
    {
        $this->router()->setAttribute('is_management', true)->save();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->get('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertStatus(404);
    }

    public function testGetHiddenAdminPasses()
    {
        $this->router()->setAttribute('is_management', true)->save();

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/firewall-policies/' . $this->firewallPolicy()->id)->assertStatus(200);
    }
}
