<?php

namespace Tests\Unit\Jobs\Router;

use App\Events\V2\Task\Created;
use App\Jobs\Router\CreateManagementRouter;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use App\Tasks\Vpc\CreateManagementInfrastructure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateManagementRouterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCreateManagementRouter()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $task = new Task([
            'id' => 'sync-1',
            'name' => CreateManagementInfrastructure::$name,
            'data' => [
                'availability_zone_id' => $this->router()->availability_zone_id
            ]
        ]);
        $task->resource()->associate($this->vpc());
        $task->save();

        dispatch(new CreateManagementRouter($task));

        $task->refresh();

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof Router
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        $event = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        })->first()[0];
        $event->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new CreateManagementRouter($task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $managementRouter = Router::findOrFail($task->data['management_router_id']);
        $this->assertTrue($managementRouter->is_management);
    }

    public function testCreateManagementRouterExistsSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->router()->setAttribute('is_management', true)->saveQuietly();

        $task = new Task([
            'id' => 'sync-1',
            'name' => CreateManagementInfrastructure::$name,
            'data' => [
                'availability_zone_id' => $this->router()->availability_zone_id
            ]
        ]);
        $task->resource()->associate($this->vpc());
        $task->save();

        dispatch(new CreateManagementRouter($task));

        $task->refresh();

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof Router
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->assertEquals($this->router()->id, $task->data['management_router_id']);
    }

    public function testCreateManagementRouterNoAvailabilityZoneFails()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => CreateManagementInfrastructure::$name,
                'data' => []
            ]);
            $task->resource()->associate($this->vpc());
            $task->save();
            return $task;
        });

        dispatch(new CreateManagementRouter($task));

        Event::assertDispatched(JobFailed::class);
        Event::assertNotDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}