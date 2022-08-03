<?php

namespace Tests\Unit\Jobs\Nic;

use App\Jobs\Nic\CheckIpAssignment;
use App\Models\V2\IpAddress;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class CheckIpAssignmentTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function testClusterIpAssignedFails()
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

        // Assign a cluster IP to the NIC
        $this->nic()->ipAddresses()->save($this->vip()->ipAddress);

        $task = $this->createSyncDeleteTask($this->nic());

        dispatch(new CheckIpAssignment($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Failed to delete NIC ' . $this->nic()->id . ', ' . IpAddress::TYPE_CLUSTER . ' IP detected';
        });
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        // Assign a DHCP address to the NIC
        $this->nic()->assignIpAddress();

        $task = $this->createSyncDeleteTask($this->nic());

        dispatch(new CheckIpAssignment($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertNotDispatched(JobFailed::class);
    }
}
