<?php

namespace Tests\unit\Jobs\Router;

use App\Events\V2\Task\Created;
use App\Jobs\Router\DeleteManagementRouters;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteManagementRoutersTest extends TestCase
{
    protected Task $task;

    public function testDeleteManagementRouter()
    {
        Event::fake(Created::class);
        $this->router()->setAttribute('is_management', true)->saveQuietly();
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpc());
        });

        $job = new DeleteManagementRouters($this->task);
        $job->handle();
        $this->assertTrue(in_array($this->router()->id, $this->task->data['management_router_ids']));
        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });
    }

    public function testSkipNonManagementRouter()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpc());
        });

        $job = new DeleteManagementRouters($this->task);
        $job->handle();

        $this->assertEquals(0, count($this->task->data['management_router_ids']));
    }
}
