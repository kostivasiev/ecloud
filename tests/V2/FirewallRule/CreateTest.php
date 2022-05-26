<?php

namespace Tests\V2\FirewallRule;

use App\Events\V2\Task\Created;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();
        Event::fake([Created::class]);
    }

    public function testValidDataSucceeds()
    {
        $this->asAdmin()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => '192.168.100.1/24',
                'destination' => '212.22.18.10/24',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true
            ])->assertStatus(202);

        $this->assertDatabaseHas('firewall_rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '192.168.100.1/24',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud');

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testFirewallPolicyFailed()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->firewallPolicy());
            $model->save();
        });

        $this->asAdmin()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => '192.168.100.1/24',
                'destination' => '212.22.18.10/24',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true
            ])->assertJsonFragment(
                [
                    'title' => 'Validation Error',
                    'detail' => 'The specified firewall policy id resource currently has the status of \'failed\' and cannot be used',
                ]
            )->assertStatus(422);

        Event::assertNotDispatched(Created::class);
    }

    public function testSourceANYSucceeds()
    {
        $this->asAdmin()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => 'ANY',
                'destination' => '212.22.18.10/24',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true
            ])->assertStatus(202);

        $this->assertDatabaseHas('firewall_rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => 'ANY',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud');

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testDestinationANYSucceeds()
    {
        $this->asAdmin()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => '212.22.18.10/24',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true
            ])->assertStatus(202);

        $this->assertDatabaseHas('firewall_rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '212.22.18.10/24',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud');

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testMissingSourceFails()
    {
        $this->asAdmin()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => '',
                'destination' => '212.22.18.10/24',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true
            ])->assertStatus(422);

        Event::assertNotDispatched(Created::class);
    }

    public function testMissingDestinationFails()
    {
        $this->asAdmin()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => '212.22.18.10/24',
                'destination' => '',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true
            ])->assertStatus(422);

        Event::assertNotDispatched(Created::class);
    }

    public function testPortsValidSucceeds()
    {
        $this->asAdmin()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => '212.22.18.10/24',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'ports' => [
                    [
                        'source' => '80',
                        'destination' => '443',
                        'protocol' => 'TCP'
                    ]
                ],
                'enabled' => true
            ])->assertStatus(202);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testPortsInvalidFails()
    {
        $this->asAdmin()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => '212.22.18.10/24',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'ports' => [
                    [
                        'destination' => 'ANY',
                        'protocol' => 'TCP'
                    ]
                ],
                'enabled' => true
            ])->assertStatus(422);

        Event::assertNotDispatched(Created::class);
    }

    public function testCreateRuleForSystemPolicyFailsForUser()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();

        $this->asUser()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => '212.22.18.10/24',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'ports' => [
                    [
                        'destination' => 'ANY',
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                    ]
                ],
                'enabled' => true
            ])->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified resource is not editable',
            ])->assertStatus(403);
    }

    public function testCreateRuleForSystemPolicySucceedsForAdmin()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();

        $this->asAdmin()
            ->post('/v2/firewall-rules', [
                'name' => 'Demo firewall rule 1',
                'sequence' => 10,
                'firewall_policy_id' => $this->firewallPolicy()->id,
                'source' => '212.22.18.10/24',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'ports' => [
                    [
                        'destination' => 'ANY',
                        'protocol' => 'TCP',
                        'source' => 'ANY',
                    ]
                ],
                'enabled' => true
            ])->assertStatus(202);
    }
}
