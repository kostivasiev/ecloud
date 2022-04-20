<?php

namespace Tests\V2\FirewallPolicy;

use App\Events\V2\Task\Created;
use App\Models\V2\FirewallPolicy;
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
        $this->firewallPolicy()
            ->setAttribute('type', FirewallPolicy::TYPE_SYSTEM)
            ->saveQuietly();

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
}
