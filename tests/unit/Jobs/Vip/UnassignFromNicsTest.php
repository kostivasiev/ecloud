<?php

namespace Tests\unit\Jobs\Vip;

use App\Events\V2\Task\Created;
use App\Jobs\Vip\UnassignFromNics;
use App\Models\V2\IpAddress;
use App\Models\V2\Task;
use App\Tasks\Nic\DisassociateIp;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class UnassignFromNicsTest extends TestCase
{
    use VipMock, LoadBalancerMock;

    public IpAddress $ipAddress;

    public Task $task;

    public function setUp(): void
    {
        parent::setUp();
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->ipAddress = $this->vip()->assignClusterIp();

        // Create am instance/node and associate it with the load balancer
        $this->loadBalancerNode();

        // Create a NIC on the instance/mode
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        // Assign the cluster IP to the nic
        $this->nic()->ipAddresses()->save($this->ipAddress);

        $this->task = $this->createSyncDeleteTask($this->vip());
    }

    public function testUnassignFromNicsSuccess()
    {
        dispatch(new UnassignFromNics($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == DisassociateIp::$name;
        });

        $this->task->refresh();

        $this->assertNotNull($this->task->data['task.' . DisassociateIp::$name . '.ids']);

        // Mark the disassociate_ip Task as completed
        $event = Event::dispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == DisassociateIp::$name;
        })->first()[0];

        $event->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new UnassignFromNics($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testUnassignFromNicsNotSyncedReleases()
    {
        dispatch(new UnassignFromNics($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == DisassociateIp::$name;
        });

        $this->task->refresh();

        $this->assertNotNull($this->task->data['task.' . DisassociateIp::$name . '.ids']);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}