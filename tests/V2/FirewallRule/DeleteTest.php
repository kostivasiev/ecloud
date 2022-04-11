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

        $this->asAdmin()
            ->delete('/v2/firewall-rules/' . $this->firewallRule->id)
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testLockedPolicyPreventsDeleteForUser()
    {
        $this->firewallPolicy()->setAttribute('locked', true)->saveQuietly();

        $this->asUser()
            ->delete('/v2/firewall-rules/' . $this->firewallRule->id)
            ->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified resource is locked',
                'status' => 403,
            ])->assertStatus(403);
    }

    public function testLockedPolicyAllowsDeleteForAdmin()
    {
        Event::fake([\App\Events\V2\Task\Created::class]);

        $this->firewallPolicy()->setAttribute('locked', true)->saveQuietly();

        $this->asAdmin()
            ->delete('/v2/firewall-rules/' . $this->firewallRule->id)
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }
}
