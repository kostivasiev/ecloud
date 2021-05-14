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
    protected NetworkRule $dhcpIngressNetworkRule;
    protected NetworkRule $dhcpEgressNetworkRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $this->networkRule = factory(NetworkRule::class)->make([
                'id' => 'nr-test',
                'name' => 'nr-test',
            ]);

            $this->networkPolicy()->networkRules()->save($this->networkRule);
        });
    }

    public function testUpdateResource()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $this->patch(
            '/v2/network-rules/nr-test',
            [
                'action' => 'REJECT',
            ]
        )->seeInDatabase(
            'network_rules',
            [
                'id' => 'nr-test',
                'action' => 'REJECT',
            ],
            'ecloud'
        )->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testCanNotEditDhcpRules()
    {
        Model::withoutEvents(function () {
            $this->dhcpIngressNetworkRule = factory(NetworkRule::class)->make([
                'id' => 'nr-' . uniqid(),
                'name' => NetworkRule::TYPE_DHCP_INGRESS,
                'sequence' => 5001,
                'source' =>  '10.0.0.2',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'IN',
                'enabled' => true,
                'type' => NetworkRule::TYPE_DHCP_INGRESS,
            ]);

            $this->networkPolicy()->networkRules()->save($this->dhcpIngressNetworkRule);

            $this->dhcpEgressNetworkRule = factory(NetworkRule::class)->make([
                'id' => 'nr-' . uniqid(),
                'name' => NetworkRule::TYPE_DHCP_EGRESS,
                'sequence' => 5002,
                'source' =>  'ANY',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'OUT',
                'enabled' => true,
                'type' => NetworkRule::TYPE_DHCP_EGRESS,
            ]);

            $this->networkPolicy()->networkRules()->save($this->dhcpEgressNetworkRule);
        });

        $this->patch('/v2/network-rules/' . $this->dhcpIngressNetworkRule->id)->assertResponseStatus(403);

        $this->patch('/v2/network-rules/' . $this->dhcpEgressNetworkRule->id)->assertResponseStatus(403);
    }
}