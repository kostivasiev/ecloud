<?php

namespace Tests\V2\FirewallPolicy;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected array $oldData;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        $this->oldData = [
            'name' => 'Demo Firewall Policy 1',
        ];
        $this->firewallPolicy()->fill($this->oldData)->saveQuietly();
        Event::fake([Created::class]);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Updated Firewall Policy 1',
        ];
        $this->asAdmin()
            ->patch(
                '/v2/firewall-policies/' . $this->firewallPolicy()->id,
                $data
            )->assertStatus(202);

        $this->firewallPolicy()->refresh();
        $this->assertEquals($data['name'], $this->firewallPolicy()->name);
        $this->assertNotEquals($this->oldData['name'], $this->firewallPolicy()->name);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testLockedPolicyDoesNotUpdate()
    {
        $this->firewallPolicy()->setAttribute('locked', true)->saveQuietly();

        $this->asUser()
            ->patch(
                '/v2/firewall-policies/' . $this->firewallPolicy()->id,
                [
                    'name' => 'Updated Firewall Policy 1',
                ]
            )->assertJsonFragment([
                'title' => 'Forbidden',
                'detail' => 'The specified resource is locked',
                'status' => 403,
            ])->assertStatus(403);
    }

    public function testAdminCanLockPolicy()
    {
        // make sure policy is unlocked
        $this->firewallPolicy()->setAttribute('locked', false)->saveQuietly();

        $this->asAdmin()
            ->put('/v2/firewall-policies/' . $this->firewallPolicy()->id . '/lock')
            ->assertStatus(204);
        $this->firewallPolicy()->refresh();
        $this->assertTrue($this->firewallPolicy()->locked);
    }

    public function testAdminCanUnlockPolicy()
    {
        // make sure policy is unlocked
        $this->firewallPolicy()->setAttribute('locked', true)->saveQuietly();

        $this->asAdmin()
            ->put('/v2/firewall-policies/' . $this->firewallPolicy()->id . '/unlock')
            ->assertStatus(204);
        $this->firewallPolicy()->refresh();
        $this->assertFalse($this->firewallPolicy()->locked);
    }

    public function testUserCannotLockPolicy()
    {
        // make sure policy is unlocked
        $this->firewallPolicy()->setAttribute('locked', false)->saveQuietly();

        $this->asUser()
            ->put('/v2/firewall-policies/' . $this->firewallPolicy()->id . '/lock')
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
            ])->assertStatus(401);
        $this->firewallPolicy()->refresh();
        $this->assertFalse($this->firewallPolicy()->locked);
    }

    public function testUserCannotUnlockPolicy()
    {
        // make sure policy is unlocked
        $this->firewallPolicy()->setAttribute('locked', true)->saveQuietly();

        $this->asUser()
            ->put('/v2/firewall-policies/' . $this->firewallPolicy()->id . '/unlock')
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
            ])->assertStatus(401);
        $this->firewallPolicy()->refresh();
        $this->assertTrue($this->firewallPolicy()->locked);
    }
}
