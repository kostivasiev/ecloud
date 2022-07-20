<?php

namespace Tests\Unit\Jobs\Router;

use App\Events\V2\Task\Created;
use App\Jobs\Router\DeployManagementInfrastructure;
use App\Tasks\Vpc\CreateManagementInfrastructure;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeployManagementInfrastructureTest extends TestCase
{
    public function testDeployManagementInfrastructure()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $task = $this->createSyncUpdateTask($this->router());

        dispatch(new DeployManagementInfrastructure($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == CreateManagementInfrastructure::TASK_NAME;
        });

        $task->refresh();

        $this->assertNotNull($task->data['task.' . CreateManagementInfrastructure::TASK_NAME . '.id']);

        // Mark the task as completed
        $createManagementInfrastructureTask = Event::dispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == CreateManagementInfrastructure::TASK_NAME;
        })->first()[0];

        $createManagementInfrastructureTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new DeployManagementInfrastructure($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    //TODO: Bulk these tests out a bit
}
