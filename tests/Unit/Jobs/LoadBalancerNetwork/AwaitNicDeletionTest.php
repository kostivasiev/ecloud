<?php

namespace Tests\Unit\Jobs\LoadBalancerNetwork;

use App\Jobs\LoadBalancerNetwork\AwaitNicDeletion;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class AwaitNicDeletionTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function testAwaitsNicDeletionSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

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

        dispatch(new AwaitNicDeletion($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        $this->nic()->delete();

        dispatch(new AwaitNicDeletion($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testAwaitsNicDeletionReleased()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        // Create a NIC on the instance/node
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        // Assign a cluster IP to the NIC - This should cause the job to fail / time out
        $this->nic()->ipAddresses()->save($this->vip()->ipAddress);

        $task = $this->createSyncDeleteTask($this->loadBalancerNetwork());

        dispatch(new AwaitNicDeletion($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
