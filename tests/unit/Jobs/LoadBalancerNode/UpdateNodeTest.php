<?php

namespace Tests\unit\Jobs\LoadBalancerNode;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancerNode\UpdateNode;
use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Router;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminNodeClient;
use UKFast\SDK\SelfResponse;

class UpdateNodeTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->router()->setAttribute('is_management', true)->saveQuietly();
        $this->nic()
            ->setAttribute('name', 'Management NIC')
            ->setAttribute('instance_id', $this->loadBalancerInstance()->id)
            ->saveQuietly();
        $this->nic()->ipAddresses()->save(IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => IpAddress::TYPE_NORMAL,
            'ip_address' => '1.1.1.1'
        ]));
    }

    public function testSuccess()
    {
        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')->andReturnSelf();
            $mock->allows('nodes')->andReturnUsing(function () {
                $nodeMock = \Mockery::mock(AdminNodeClient::class)->makePartial();
                $nodeMock->allows('updateEntity')
                    ->withAnyArgs()
                    ->andReturnUsing(function () {
                        $mockSelfResponse = \Mockery::mock(SelfResponse::class)->makePartial();
                        $mockSelfResponse->allows('getId')->andReturns($this->loadBalancerNode()->id);
                        return $mockSelfResponse;
                    });
                return $nodeMock;
            });
            return $mock;
        });

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new UpdateNode($this->createSyncUpdateTask($this->loadBalancerNode())));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testGetManagementNic()
    {
        $job = new UpdateNode($this->createSyncUpdateTask($this->loadBalancerNode()));

        $nic = Nic::factory()->create([
            'id' => 'nic-' . uniqid(),
            'mac_address' => 'AA:AA:AA:AA:AA:AA',
            'network_id' => Network::factory()->create([
                'id' => 'net-' . uniqid(),
                'subnet' => '10.0.0.0/24',
                'router_id' => Router::factory()->create([
                    'id' => 'rtr-' . uniqid(),
                    'vpc_id' => $this->vpc()->id,
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'router_throughput_id' => $this->routerThroughput()->id,
                ])->id
            ])->id,
        ]);
        $nic->ipAddresses()->save(IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => IpAddress::TYPE_NORMAL,
            'ip_address' => '2.2.2.2'
        ]));
        $nic->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $this->assertEquals($this->nic()->id, $job->getManagementNic()->id);
        $this->assertEquals('1.1.1.1', $job->getManagementNic()->ip_address);
    }
}
