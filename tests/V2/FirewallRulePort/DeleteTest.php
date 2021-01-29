<?php

namespace Tests\V2\FirewallRulePort;

use App\Events\V2\FirewallRulePort\Deleted;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    use DatabaseMigrations;

    protected FirewallRule $firewallRule;
    protected FirewallRulePort $firewallRulePort;

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
                new Response(200, [], json_encode(['publish_status' => 'REALIZED'])),
                new Response(200, [], json_encode(['publish_status' => 'REALIZED'])),
            );

        $this->firewallRule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->firewallPolicy()->id,
        ]);

        $this->firewallRulePort = factory(FirewallRulePort::class)->create([
            'firewall_rule_id' => $this->firewallRule->id,
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/firewall-rule-ports/' . $this->firewallRulePort->id,
            [],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $firewallRulePort = FirewallRulePort::withTrashed()->findOrFail($this->firewallRulePort->id);
        $this->assertNotNull($firewallRulePort->deleted_at);

        Event::assertDispatched(Deleted::class, function ($job) {
            return $job->model->id === $this->firewallRulePort->id;
        });
    }

}
