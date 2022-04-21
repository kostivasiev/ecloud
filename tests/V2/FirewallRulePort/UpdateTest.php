<?php

namespace Tests\V2\FirewallRulePort;

use App\Events\V2\Task\Created;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $firewallRule;
    protected $firewallRulePort;

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

    public function testValidDataSucceeds()
    {
        $this->patch(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
            [
                'name' => 'Changed',
                'protocol' => 'UDP',
                'source' => 'ANY',
                'destination' => '80'
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);

        $this->assertDatabaseHas(
            'firewall_rule_ports',
            [
                'id' => $this->firewallRulePort->id,
                'name' => 'Changed',
                'protocol' => 'UDP',
                'source' => 'ANY',
                'destination' => '80'
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testUpdateWithICMPValues()
    {
        $this->patch(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
            [
                'name' => 'Changed',
                'protocol' => 'ICMPv4',
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(202);

        $this->assertDatabaseHas(
            'firewall_rule_ports',
            [
                'id' => $this->firewallRulePort->id,
                'name' => 'Changed',
                'protocol' => 'ICMPv4',
                'source' => null,
                'destination' => null
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testEmptySourceFails()
    {
        $this->patch(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
            [
                'name' => 'Changed',
                'protocol' => 'UDP',
                'source' => '',
                'destination' => '80'
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(422);

        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testEmptyDestinationFails()
    {
        $this->patch(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
            [
                'name' => 'Changed',
                'protocol' => 'UDP',
                'source' => '444',
                'destination' => ''
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertStatus(422);

        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testLockedPolicySucceedsAsAdmin()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();

        $this->asAdmin()
            ->patch(
                '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
                [
                    'name' => 'Changed',
                    'protocol' => 'UDP',
                    'source' => 'ANY',
                    'destination' => '80'
                ]
            )->assertStatus(202);

        $this->assertDatabaseHas(
            'firewall_rule_ports',
            [
                'id' => $this->firewallRulePort->id,
                'name' => 'Changed',
                'protocol' => 'UDP',
                'source' => 'ANY',
                'destination' => '80'
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testLockedPolicyFailsAsUser()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();

        $this->asUser()
            ->patch(
                '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
                [
                    'name' => 'Changed',
                    'protocol' => 'UDP',
                    'source' => 'ANY',
                    'destination' => '80'
                ]
            )->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified resource is not editable',
            ])->assertStatus(403);
    }
}
