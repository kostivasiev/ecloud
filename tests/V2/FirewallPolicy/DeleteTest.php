<?php

namespace Tests\V2\FirewallPolicy;

use App\Events\V2\Task\Created;
use App\Models\V2\FirewallPolicy;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake(Created::class);
    }

    public function testSuccessfulDelete()
    {
        $this->asAdmin()
            ->delete('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertStatus(202);
    }

    public function testUserCannotDeleteLockedPolicy()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();
        $this->asUser()
            ->delete('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The System policy is not editable',
            ])->assertStatus(403);
    }

    public function testAdminCanDeleteLockedPolicy()
    {
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();
        $this->asAdmin()
            ->delete('/v2/firewall-policies/' . $this->firewallPolicy()->id)
            ->assertStatus(202);
    }
}
