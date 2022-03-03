<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\FirewallRule;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    protected FirewallRule $firewallRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        $this->firewallRule = FirewallRule::factory()->create([
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
        ])->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }
}
