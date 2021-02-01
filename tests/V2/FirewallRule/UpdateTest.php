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
            ->andReturn(
                new Response(200, [], ''),
            );

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturn(
                new Response(200, [], json_encode(['publish_status' => 'REALIZED']))
            );

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
            ->assertResponseStatus(200);
    }
}
