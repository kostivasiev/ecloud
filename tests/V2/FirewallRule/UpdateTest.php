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
        $this->patch(
            '/v2/firewall-rules/' . $this->firewallRule->id,
            [
                'name' => 'Changed',
                'ports' => [
                    [
                        'source' => "ANY",
                    ]
                ]
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
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
                'detail' => 'The System policy is not editable'
            ])->assertStatus(403);
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
}
