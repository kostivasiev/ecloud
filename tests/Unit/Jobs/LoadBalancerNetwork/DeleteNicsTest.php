<?php

namespace Tests\Unit\Jobs\LoadBalancerNetwork;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancerNetwork\DeleteNics;
use App\Models\V2\IpAddress;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class DeleteNicsTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function testDeletesNetworkNic()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

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

        dispatch(new DeleteNics($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        $task->refresh();

        // Mark the delete sync task as completed
        $syncTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        })->first()[0];
        $syncTask->model->setAttribute('completed', true)->saveQuietly();
        $syncTask->model->resource->delete();

        dispatch(new DeleteNics($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testClusterIpAssignedFails()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        // Create a NIC on the instance/node
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        // Assign a cluster IP to the NIC
        $this->nic()->ipAddresses()->save($this->vip()->ipAddress);

        $task = $this->createSyncDeleteTask($this->loadBalancerNetwork());

        dispatch(new DeleteNics($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to delete NIC ' . $this->nic()->id . ', ' . IpAddress::TYPE_CLUSTER . ' IP detected';
        });

        Event::assertNotDispatched(Created::class);
    }

    public function testReleasedWhenSyncing()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

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

        dispatch(new DeleteNics($task));

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
