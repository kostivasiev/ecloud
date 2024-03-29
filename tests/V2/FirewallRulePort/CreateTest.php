<?php

namespace Tests\V2\FirewallRulePort;

use App\Events\V2\Task\Created;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Task;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $firewallRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        $this->firewallRule = FirewallRule::factory()->create([
            'firewall_policy_id' => $this->firewallPolicy()->id,
        ]);

        Event::fake([Created::class]);
    }

    public function testValidDataSucceeds()
    {
        $this->post('/v2/firewall-rule-ports', [
            'firewall_rule_id' => $this->firewallRule->id,
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555'
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->assertStatus(202);

        $this->assertDatabaseHas(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testValidICMPDataSucceeds()
    {
        $this->post('/v2/firewall-rule-ports', [
            'firewall_rule_id' => $this->firewallRule->id,
            'protocol' => 'ICMPv4',
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->assertStatus(202);

        $this->assertDatabaseHas(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'ICMPv4',
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testSourceANYSucceeds()
    {
        $this->post('/v2/firewall-rule-ports', [
            'firewall_rule_id' => $this->firewallRule->id,
            'protocol' => 'TCP',
            'source' => 'ANY',
            'destination' => '555'
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->assertStatus(202);

        $this->assertDatabaseHas(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => 'ANY',
                'destination' => '555'
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testDestinationANYSucceeds()
    {
        $this->post('/v2/firewall-rule-ports', [
            'firewall_rule_id' => $this->firewallRule->id,
            'protocol' => 'TCP',
            'source' => '444',
            'destination' => 'ANY'
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->assertStatus(202);

        $this->assertDatabaseHas(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '444',
                'destination' => 'ANY'
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testMissingSourceFails()
    {
        $this->post('/v2/firewall-rule-ports', [
            'firewall_rule_id' => $this->firewallRule->id,
            'protocol' => 'TCP',
            'source' => '',
            'destination' => 'ANY'
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->assertStatus(422);

        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testMissingDestinationFails()
    {
        $this->post('/v2/firewall-rule-ports', [
            'firewall_rule_id' => $this->firewallRule->id,
            'protocol' => 'TCP',
            'source' => 'ANY',
            'destination' => ''
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->assertStatus(422);

        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testCreatePortSystemPolicyFailsForUser()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();

        $this->asUser()
            ->post('/v2/firewall-rule-ports', [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ])->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified resource is not editable',
            ])->assertStatus(403);
    }

    public function testCreatePortSystemPolicySucceedsForAdmin()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();

        $this->asAdmin()
            ->post('/v2/firewall-rule-ports', [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ])->assertStatus(202);

        $this->assertDatabaseHas(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testDuplicatePortRuleDetected()
    {
        $response = $this->asUser()
            ->post('/v2/firewall-rule-ports', [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ])->assertStatus(202);

        $this->assertDatabaseHas(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ],
            'ecloud'
        );

        $task = Task::findOrFail(json_decode($response->getContent())->data->task_id);
        $task->setAttribute('completed', true)->saveQuietly();

        $this->asUser()
            ->post('/v2/firewall-rule-ports', [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ])->assertJsonFragment([
                'detail' => 'This port configuration already exists',
            ])->assertStatus(422);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testDuplicatePortListRuleDetectedFails()
    {
        FirewallRulePort::factory()
            ->for($this->firewallRule)
            ->create([
                'protocol' => 'TCP',
                'source' => 400,
                'destination' => 500
            ]);

        $this->asUser()
            ->post('/v2/firewall-rule-ports', [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '400,401-499',
                'destination' => '500,80,500-510'
            ])->assertJsonFragment([
                'detail' => 'There are port(s) conflicting with existing rules in this configuration',
            ])->assertStatus(422);
    }
}
