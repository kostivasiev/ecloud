<?php

namespace Tests\V2\NetworkRule;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected NetworkPolicy $networkPolicy;
    protected NetworkRule $networkRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $this->networkRule = NetworkRule::factory()->make([
                'id' => 'nr-test',
                'name' => 'nr-test',
            ]);

            $this->networkPolicy()->networkRules()->save($this->networkRule);
        });
    }

    public function testUpdateResource()
    {
        Event::fake(\App\Events\V2\Task\Created::class);
        $this->vpc()->advanced_networking = true;
        $this->vpc()->saveQuietly();

        $this->patch(
            '/v2/network-rules/nr-test',
            [
                'action' => 'REJECT',
            ]
        )->assertStatus(202);
        $this->assertDatabaseHas(
            'network_rules',
            [
                'id' => 'nr-test',
                'action' => 'REJECT',
            ],
            'ecloud'
        );

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testCanNotEditDhcpRules()
    {
        $dhcpNetworkRule = Model::withoutEvents(function () {
            $dhcpNetworkRule = NetworkRule::factory()->make([
                'id' => 'nr-' . uniqid(),
                'name' => NetworkRule::TYPE_DHCP,
                'sequence' => 5001,
                'source' =>  '10.0.0.2',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true,
                'type' => NetworkRule::TYPE_DHCP,
            ]);

            $this->networkPolicy()->networkRules()->save($dhcpNetworkRule);
            return $dhcpNetworkRule;
        });

        $this->patch('/v2/network-rules/' . $dhcpNetworkRule->id)->assertStatus(403);
    }

    public function testLockedPolicyAmendRuleFails()
    {
        $this->networkPolicy()->setAttribute('locked', true)->saveQuietly();
        $this->patch(
            '/v2/network-rules/nr-test',
            [
                'action' => 'REJECT',
            ]
        )->assertJsonFragment([
            'title' => 'Forbidden',
            'detail' => 'The specified resource is locked',
            'status' => 403,
        ])->assertStatus(403);
    }
}