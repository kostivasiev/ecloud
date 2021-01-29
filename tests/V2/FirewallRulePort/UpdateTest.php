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
            ->andReturn(
                new Response(200, [], ''),
            );

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturn(
                new Response(200, [], json_encode(['publish_status' => 'REALIZED']))
            );

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
                'source' => '10.0.0.1',
                'destination' => '192.168.1.2'
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
                'source' => '10.0.0.1',
                'destination' => '192.168.1.2'
            ],
            'ecloud'
        )->assertResponseStatus(200);

        Event::assertDispatched(FirewallPolicySaved::class, function ($job) {
            return $job->model->id === $this->firewallPolicy()->id;
        });

        Event::assertDispatched(FirewallRulePortSaved::class, function ($job) {
            return $job->model->id === $this->firewallRulePort->id;
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
        )->assertResponseStatus(200);

        Event::assertDispatched(FirewallPolicySaved::class, function ($job) {
            return $job->model->id === $this->firewallPolicy()->id;
        });

        Event::assertDispatched(FirewallRulePortSaved::class, function ($job) {
            return $job->model->id === $this->firewallRulePort->id;
        });
    }
}
