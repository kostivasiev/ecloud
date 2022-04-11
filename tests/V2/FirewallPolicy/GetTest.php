<?php

namespace Tests\V2\FirewallPolicy;

use App\Models\V2\FirewallRule;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected FirewallRule $firewallRule;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();
        $this->firewallRule = FirewallRule::factory()
            ->for($this->firewallPolicy())
            ->create();
    }

    public function testGetCollection()
    {
        $this->asAdmin()
            ->get(
                '/v2/firewall-policies'
            )->assertJsonFragment([
                'id' => $this->firewallPolicy()->id,
                'name' => $this->firewallPolicy()->name,
                'sequence' => $this->firewallPolicy()->sequence,
                'router_id' => $this->router()->id,
            ])->assertStatus(200);
    }

    public function testGetResource()
    {
        $this->asAdmin()
            ->get(
                '/v2/firewall-policies/' . $this->firewallPolicy()->id
            )->assertJsonFragment([
                'id' => $this->firewallPolicy()->id,
                'name' => $this->firewallPolicy()->name,
                'sequence' => $this->firewallPolicy()->sequence,
                'router_id' => $this->router()->id,
            ])->assertStatus(200);
    }

    public function testGetFirewallPolicyFirewallRules()
    {
        $this->asAdmin()
            ->get(
                '/v2/firewall-policies/' . $this->firewallPolicy()->id . '/firewall-rules'
            )->assertJsonFragment([
                'id' => $this->firewallRule->id,
                'firewall_policy_id' => $this->firewallPolicy()->id
            ])->assertStatus(200);
    }

    public function testGetHiddenNotAdminFails()
    {
        $this->router()->setAttribute('is_management', true)->save();
        $this->asUser()
            ->get('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertStatus(404);
    }

    public function testGetHiddenAdminPasses()
    {
        $this->router()->setAttribute('is_management', true)->save();
        $this->asAdmin()
            ->get('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertStatus(200);
    }

    public function testUserCannotSeeLockedState()
    {
        $this->asUser()
            ->get('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertJsonMissing([
                'locked' => false
            ])->assertStatus(200);

        $this->firewallPolicy()->setAttribute('locked', true)->saveQuietly();

        $this->asUser()
            ->get('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertJsonMissing([
                'locked' => true
            ])->assertStatus(200);
    }

    public function testAdminCanSeeLockedState()
    {
        $this->asAdmin()
            ->get('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertJsonFragment([
                'locked' => false
            ])->assertStatus(200);

        $this->firewallPolicy()->setAttribute('locked', true)->saveQuietly();

        $this->asAdmin()
            ->get('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertJsonFragment([
                'locked' => true
            ])->assertStatus(200);
    }
}
