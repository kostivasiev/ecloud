<?php

namespace Tests\V2\FirewallRule;

use App\Events\V2\FirewallPolicy\Saved as FirewallPolicySaved;
use App\Events\V2\FirewallRule\Saved as FirewallRuleSaved;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
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
        ])->seeInDatabase('firewall_rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '192.168.100.1/24',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud')->assertResponseStatus(202);
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
        ])->seeInDatabase('firewall_rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => 'ANY',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud')->assertResponseStatus(202);
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
        ])->seeInDatabase('firewall_rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '212.22.18.10/24',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud')->assertResponseStatus(202);
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
        ])->assertResponseStatus(422);
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
        ])->assertResponseStatus(422);
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
        ])->assertResponseStatus(202);
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
        ])->assertResponseStatus(422);
    }
}
