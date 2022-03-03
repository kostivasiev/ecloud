<?php

namespace Tests\V2\Nic;

use App\Events\V2\Task\Created;
use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class AssociateIpTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
    }

    public function testNotClusterIpFails()
    {
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => 'normal'
        ]);

        $this->post(
            '/v2/nics/' . $this->nic()->id . '/ip-addresses',
            [
                'ip_address_id' => $ipAddress->id,
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The ip address id must be of type ' . IpAddress::TYPE_CLUSTER,
            'status' => 422,
            'source' => 'ip_address_id'
        ])->assertResponseStatus(422);
    }

    public function testIpNotSameNetworkAsNicFails()
    {
        $network = Network::factory()->create([
            'id' => 'net-test-2',
            'name' => 'Manchester Network',
            'subnet' => '10.0.0.0/24',
            'router_id' => $this->router()->id
        ]);
        
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $network->id,
            'type' => 'normal'
        ]);

        $this->post(
            '/v2/nics/' . $this->nic()->id . '/ip-addresses',
            [
                'ip_address_id' => $ipAddress->id,
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The ip address id must be on the same network as the NIC',
            'status' => 422,
            'source' => 'ip_address_id'
        ])->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        Event::fake([Created::class]);

        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => 'cluster'
        ]);

        $this->post(
            '/v2/nics/' . $this->nic()->id . '/ip-addresses',
            [
                'ip_address_id' => $ipAddress->id,
            ]
        )->assertResponseStatus(202);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'associate_ip';
        });
    }
}
