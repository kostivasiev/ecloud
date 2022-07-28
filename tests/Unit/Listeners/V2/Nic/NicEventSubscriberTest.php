<?php

namespace Tests\Unit\Listeners\V2\Nic;

use App\Events\V2\Task\Created;
use App\Listeners\V2\Nic\NicEventSubscriber;
use App\Support\Sync;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class NicEventSubscriberTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->subscriber = \Mockery::mock(NicEventSubscriber::class)->shouldAllowMockingProtectedMethods()->makePartial();
    }

    public function testDeleteLoadBalancerNetworkNicsSuccess()
    {
        Event::fake([Created::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        // Create a NIC on the instance/mode
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $this->assertCount(1, $this->loadBalancerInstance()->nics);

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        $task = $this->createSyncDeleteTask($this->loadBalancerNetwork());

        $this->subscriber->handleTaskCreatedEvent(new Created($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });
    }

    public function testDeleteLoadBalancerNetworkNicsClusterIpAssigned()
    {
        Event::fake([Created::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        // Create a NIC on the instance/mode
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $this->assertCount(1, $this->loadBalancerInstance()->nics);

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        // Assign a cluster IP to the NIC
        $this->nic()->ipAddresses()->save($this->vip()->ipAddress);

        $task = $this->createSyncDeleteTask($this->loadBalancerNetwork());

        $this->subscriber->handleTaskCreatedEvent(new Created($task));

        Event::assertNotDispatched(Created::class);
    }
}
