<?php

namespace Tests\V2\FirewallRule;

use App\Events\V2\FirewallPolicy\Saved as FirewallPolicySaved;
use App\Events\V2\FirewallRule\Saved as FirewallRuleSaved;
use App\Models\V2\FirewallRule;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected FirewallRule $firewall_rule;

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

        $this->firewall_rule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->firewallPolicy()->id,
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->patch(
            '/v2/firewall-rules/' . $this->firewall_rule->id,
            [
                'name' => 'Changed',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase('firewall_rules',
                [
                    'id' => $this->firewall_rule->id,
                    'name' => 'Changed'
                ],
                'ecloud')
            ->assertResponseStatus(202);
    }

    public function testEmptySourceFails()
    {
        $this->patch(
            '/v2/firewall-rules/' . $this->firewall_rule->id,
            [
                'source' => '',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(422);
    }

    public function testEmptyDestinationFails()
    {
        $this->patch(
            '/v2/firewall-rules/' . $this->firewall_rule->id,
            [
                'destination' => '',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(422);
    }

    public function testInvalidPortFails()
    {
        $this->patch(
            '/v2/firewall-rules/' . $this->firewall_rule->id,
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
        )->assertResponseStatus(422);
    }
}
