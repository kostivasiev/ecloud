<?php

namespace Tests\Unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\AddNetworks;
use App\Jobs\LoadBalancer\DeleteNetworks;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class DeleteNetworksTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $this->loadBalancerNetwork();

        $task = $this->createSyncDeleteTask($this->loadBalancer());

        dispatch(new DeleteNetworks($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();

        $this->assertNotNull($task->data['load_balancer_network_ids']);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        $this->assertEquals([$this->loadBalancerNetwork()->id], $task->data['load_balancer_network_ids']);

        $event = Event::dispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        })->first()[0];
        $event->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new AddNetworks($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testReleasedWhenSyncing()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $this->loadBalancerNetwork();

        $task = $this->createSyncDeleteTask($this->loadBalancer());

        dispatch(new DeleteNetworks($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();

        $this->assertNotNull($task->data['load_balancer_network_ids']);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testNoNetworkSucceeds()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $task = $this->createSyncDeleteTask($this->loadBalancer());

        dispatch(new DeleteNetworks($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();

        $this->assertNotTrue(isset($task->data['load_balancer_network_ids']));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
