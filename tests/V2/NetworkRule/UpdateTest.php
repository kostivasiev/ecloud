<?php

namespace Tests\V2\NetworkRule;

use App\Events\V2\Task\Created;
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

    public function testIpTypeMismatchFails()
    {
        $this->networkRule->setAttribute('source', '10.0.0.1/24')->saveQuietly();
        $this->asUser()
            ->patch(
                '/v2/network-rules/' . $this->networkRule->id,
                [
                    'destination' => '78a6:9d0e:1937:ce40:312c:6718:0f98:400f/24',
                ]
            )->assertJsonFragment([
                'detail' => 'The source and destination attributes must be of the same IP type IPv4/IPv6',
            ])->assertStatus(422);
    }

    public function testUsingAnySucceeds()
    {
        Event::fake([Created::class]);
        $this->networkRule->setAttribute('source', '10.0.0.1/24')->saveQuietly();
        $this->asUser()
            ->patch(
                '/v2/network-rules/' . $this->networkRule->id,
                [
                    'destination' => 'ANY',
                ]
            )->assertStatus(202);
        Event::assertDispatched(Created::class);
    }
}