<?php

namespace Tests\Unit\Jobs\Vip;

use App\Events\V2\Task\Created;
use App\Jobs\Vip\AssignToNics;
use App\Tasks\Nic\AssociateIp;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class AssignToNicsTest extends TestCase
{
    use VipMock, LoadBalancerMock;

    public function testAssignToNicsSuccess()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create am instance/node and associate it with the load balancer
        $this->loadBalancerNode();

        // Create a NIC on the instance/mode
        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $task = $this->createSyncUpdateTask($this->vip());

        dispatch(new AssignToNics($task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == AssociateIp::TASK_NAME;
        });

        $task->refresh();

        $this->assertNotNull($task->data['task.' . AssociateIp::TASK_NAME . '.ids']);

        // Mark the Associate IP Task as completed
        $event = Event::dispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == AssociateIp::TASK_NAME;
        })->first()[0];

        $event->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new AssignToNics($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testAssignToNicsNotSyncedReleases()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $this->vip()->assignClusterIp();

        $this->loadBalancerNode();

        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $task = $this->createSyncUpdateTask($this->vip());

        dispatch(new AssignToNics($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == AssociateIp::TASK_NAME;
        });

        $task->refresh();

        $this->assertNotNull($task->data['task.' . AssociateIp::TASK_NAME . '.ids']);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testAssignToNicsAlreadyAssignedSkips()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $this->vip()->assignClusterIp();

        $this->loadBalancerNode();

        $this->nic()->setAttribute('instance_id', $this->loadBalancerInstance()->id)->saveQuietly();

        $task = $this->createSyncUpdateTask($this->vip());

        $this->nic()->ipAddresses()->save($this->vip()->ipAddress);

        dispatch(new AssignToNics($task));

        $task->refresh();

        $this->assertNull($task->data);
    }
}