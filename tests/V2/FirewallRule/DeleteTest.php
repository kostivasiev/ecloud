<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\FirewallRule;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
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
        Event::fake([\App\Events\V2\Task\Created::class]);

        $this->delete('/v2/firewall-rules/' . $this->firewallRule->id, [], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return count($event->model->data['rules_to_remove']) == 1 && $event->model->data['rules_to_remove'][0] == $this->firewallRule->id;
        });
    }
}
