<?php

namespace Tests\V2\FirewallRulePort;

use App\Events\V2\FirewallPolicy\Saved as FirewallPolicySaved;
use App\Events\V2\FirewallRulePort\Saved as FirewallRulePortSaved;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $firewallRule;
    protected $firewallRulePort;

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

        $this->firewallRulePort = factory(FirewallRulePort::class)->create([
            'firewall_rule_id' => $this->firewallRule->id,
        ]);
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
        )->seeInDatabase(
            'firewall_rule_ports',
            [
                'id' => $this->firewallRulePort->id,
                'name' => 'Changed',
                'protocol' => 'UDP',
                'source' => 'ANY',
                'destination' => '80'
            ],
            'ecloud'
        )->assertResponseStatus(202);
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
        )->seeInDatabase(
            'firewall_rule_ports',
            [
                'id' => $this->firewallRulePort->id,
                'name' => 'Changed',
                'protocol' => 'ICMPv4',
                'source' => null,
                'destination' => null
            ],
            'ecloud'
        )->assertResponseStatus(202);
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
        )->assertResponseStatus(422);
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
        )->assertResponseStatus(422);
    }
}
