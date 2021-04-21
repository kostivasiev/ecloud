<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\FirewallRule;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected FirewallRule $firewallRule;

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

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('delete')
            ->andReturn(
                new Response(204)
            );

        $this->firewallRule = factory(FirewallRule::class)->create([
            'id' => 'fwr-test',
            'firewall_policy_id' => $this->firewallPolicy()->id,
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->delete('v2/firewall-rules/' . $this->firewallRule->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);
        $this->assertNotFalse(FirewallRule::find($this->firewallRule->id));
    }
}
