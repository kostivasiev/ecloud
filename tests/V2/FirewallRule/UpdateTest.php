<?php

namespace Tests\V2\FirewallRule;

use App\Events\V2\Task\Created;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected FirewallRule $firewallRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        $this->firewallRule = FirewallRule::factory()->create([
            'firewall_policy_id' => $this->firewallPolicy()->id,
        ]);
    }

    public function testEmptySourceFails()
    {
        $this->patch(
            '/v2/firewall-rules/' . $this->firewallRule->id,
            [
                'source' => '',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(422);
    }

    public function testEmptyDestinationFails()
    {
        $this->patch(
            '/v2/firewall-rules/' . $this->firewallRule->id,
            [
                'destination' => '',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(422);
    }

    public function testInvalidPortFails()
    {
        $this->asAdmin()
            ->patch(
                '/v2/firewall-rules/' . $this->firewallRule->id,
                [
                    'name' => 'Changed',
                    'ports' => [
                        [
                            'source' => "ANY",
                        ]
                    ]
                ]
            )->assertStatus(422);
    }

    public function testSystemPolicyAmendRuleFails()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();
        $this->asUser()
            ->patch(
                '/v2/firewall-rules/' . $this->firewallRule->id,
                [
                    'name' => 'Changed',
                    'ports' => [
                        [
                            'source' => "ANY",
                        ]
                    ]
                ],
            )->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified resource is not editable'
            ])->assertStatus(403);
    }

    public function testSystemPolicyAmendAsAdminSucceeds()
    {
        Event::fake([Created::class]);
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();
        $this->asAdmin()
            ->patch(
                '/v2/firewall-rules/' . $this->firewallRule->id,
                [
                    'name' => 'Changed',
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'source' => "ANY",
                            'destination' => "ANY",
                        ]
                    ]
                ],
            )->assertStatus(202);
        Event::assertDispatched(Created::class);
    }

    public function testUpdateSuccessful()
    {
        Event::fake([Created::class]);
        $this->asAdmin()
            ->patch(
                '/v2/firewall-rules/' . $this->firewallRule->id,
                [
                    'name' => 'Changed',
                    'ports' => [
                        [
                            'protocol' => 'TCP',
                            'source' => "ANY",
                            'destination' => "ANY",
                        ]
                    ]
                ],
            )->assertStatus(202);
        Event::assertDispatched(Created::class);
    }

    public function testIpTypeMismatchFails()
    {
        $this->firewallRule->setAttribute('source', '10.0.0.1/24')->saveQuietly();
        $this->asUser()
            ->patch(
                '/v2/firewall-rules/' . $this->firewallRule->id,
                [
                    'destination' => '78a6:9d0e:1937:ce40:312c:6718:0f98:400f/24',
                ]
            )->assertJsonFragment([
                'detail' => 'The source and destination attributes must be of the same IP type IPv4/IPv6',
            ])->assertStatus(422);
    }

    public function testUsingAnySucceeds()
    {
        Event::fake([Created::class]);
        $this->firewallRule->setAttribute('source', '10.0.0.1/24')->saveQuietly();
        $this->asUser()
            ->patch(
                '/v2/firewall-rules/' . $this->firewallRule->id,
                [
                    'destination' => 'ANY',
                ]
            )->assertStatus(202);
        Event::assertDispatched(Created::class);
    }
}
