<?php

namespace Tests\unit\Jobs\Router;

use App\Jobs\Router\DeleteManagementRouter;
use App\Listeners\V2\TaskCreated;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteManagementRouterTest extends TestCase
{
    protected Task $task;

    public function testDeleteManagementRouter()
    {
        $this->router()->setAttribute('is_hidden', true)->saveQuietly();
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpc());
        });
        Event::fake(TaskCreated::class);
        Bus::fake();

        $job = new DeleteManagementRouter($this->task);
        $job->handle();
        $this->assertTrue(in_array($this->router()->id, $this->task->data['management_router_ids']));
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
        Event::fake(TaskCreated::class);
        Bus::fake();

        $job = new DeleteManagementRouter($this->task);
        $job->handle();

        $this->assertEquals(0, count($this->task->data['management_router_ids']));
    }
}
