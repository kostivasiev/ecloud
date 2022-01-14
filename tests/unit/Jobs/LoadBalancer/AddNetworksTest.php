<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\AddNetworks;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class AddNetworksTest extends TestCase
{
    use LoadBalancerMock;

    public function testSuccess()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $task = $this->createSyncUpdateTask(
            $this->loadBalancer(),
            ['network_ids' => [$this->network()->id]]
        );

        dispatch(new AddNetworks($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();

        $this->assertNotNull($task->data['load_balancer_network_ids']);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        $event = Event::dispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        })->first()[0];
        $event->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new AddNetworks($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testReleasedWhenSyncing()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $task = $this->createSyncUpdateTask(
            $this->loadBalancer(),
            ['network_ids' => [$this->network()->id]]
        );

        dispatch(new AddNetworks($task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });

        $task->refresh();

        $this->assertNotNull($task->data['load_balancer_network_ids']);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testNoNetworkIdsSucceeds()
    {
        Event::fake([JobFailed::class, Created::class]);

        $task = $this->createSyncUpdateTask($this->loadBalancer());

        dispatch(new AddNetworks($task));

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();

        $this->assertNotTrue(isset($task->data['load_balancer_network_ids']));
    }

    public function testNetworkNotFoundWarns()
    {
        Event::fake([JobFailed::class, Created::class]);

        $task = $this->createSyncUpdateTask(
            $this->loadBalancer(),
            ['network_ids' => ['net-123']]
        );

        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->withSomeOfArgs(AddNetworks::class . ': Failed to load network to associate with load balancer: net-123');

        dispatch(new AddNetworks($task));

        Event::assertNotDispatched(JobFailed::class);
    }
}
