<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\DeleteNetworks;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
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
        Event::fake([JobFailed::class, Created::class]);

        $this->loadBalancerNetwork();

        $task = $this->createSyncDeleteTask($this->loadBalancer());

        dispatch(new DeleteNetworks($task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        $task->refresh();

        $this->assertNotNull($task->data['load_balancer_network_ids']);

        $this->assertEquals([$this->loadBalancerNetwork()->id], $task->data['load_balancer_network_ids']);
    }

    public function testNoNetworkSucceeds()
    {
        Event::fake([JobFailed::class, Created::class]);

        $task = $this->createSyncDeleteTask($this->loadBalancer());

        dispatch(new DeleteNetworks($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();

        $this->assertNotTrue(isset($task->data['load_balancer_network_ids']));
    }
}
