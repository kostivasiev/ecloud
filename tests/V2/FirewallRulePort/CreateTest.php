<?php

namespace Tests\V2\FirewallRulePort;

use App\Events\V2\FirewallPolicy\Saved;
use App\Models\V2\FirewallRule;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $firewallRule;

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
        ])->seeInDatabase(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'TCP',
                'source' => '443',
                'destination' => '555'
            ],
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testValidICMPDataSucceeds()
    {
        $this->post('/v2/firewall-rule-ports', [
            'firewall_rule_id' => $this->firewallRule->id,
            'protocol' => 'ICMPv4'
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write'
        ])->seeInDatabase(
            'firewall_rule_ports',
            [
                'firewall_rule_id' => $this->firewallRule->id,
                'protocol' => 'ICMPv4',
            ],
            'ecloud'
        )->assertResponseStatus(201);

        Event::assertDispatched(Saved::class, function ($job) {
            return $job->model->id === $this->firewallPolicy()->id;
        });
    }
}
