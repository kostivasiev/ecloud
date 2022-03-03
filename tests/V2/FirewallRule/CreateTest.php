<?php

namespace Tests\V2\FirewallRule;

use App\Events\V2\FirewallPolicy\Saved as FirewallPolicySaved;
use App\Events\V2\FirewallRule\Saved as FirewallRuleSaved;
use App\Events\V2\Task\Created;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
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
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '192.168.100.1/24',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
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

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
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

        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '192.168.100.1/24',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertJsonFragment(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified firewall policy id resource currently has the status of \'failed\' and cannot be used',
            ]
        )->assertStatus(422);

        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testSourceANYSucceeds()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => 'ANY',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
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

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testDestinationANYSucceeds()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '212.22.18.10/24',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
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

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testMissingSourceFails()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(422);

        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testMissingDestinationFails()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '212.22.18.10/24',
            'destination' => '',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(422);

        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testPortsValidSucceeds()
    {
        $this->post('/v2/firewall-rules', [
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
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testPortsInvalidFails()
    {
        $this->post('/v2/firewall-rules', [
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
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(422);

        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }
}
