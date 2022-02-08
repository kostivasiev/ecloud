<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeleteVips;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use Tests\Mocks\Resources\LoadBalancerMock;

class DeleteVipsTest extends TestCase
{
    use LoadBalancerMock, VipMock;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        $task = $this->createSyncDeleteTask($this->loadBalancer());

        dispatch(new DeleteVips($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();

        $this->assertNotNull($task->data['vip_ids']);

        // Mark the delete sync task as completed
        $syncTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        })->first()[0];

        $syncTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new DeleteVips($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testReleasedWhenSyncing()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        // Create a VIP and assign a cluster IP to it.
        $this->vip()->assignClusterIp();

        // Create a load balancer node and instance
        $this->loadBalancerNode();

        $task = $this->createSyncDeleteTask($this->loadBalancer());

        dispatch(new DeleteVips($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
