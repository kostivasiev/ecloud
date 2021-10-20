<?php
namespace Tests\unit\Jobs\Router;

use App\Jobs\Router\CreateManagementRouter;
use App\Listeners\V2\TaskCreated;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateManagementRouterTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCreateManagementRouter()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->router());
        });
        Event::fake(TaskCreated::class);
        Bus::fake();
        $job = new CreateManagementRouter($this->task);
        $job->handle();

        $managementRouter = Router::findOrFail($this->task->data['management_router_id']);
        $this->assertTrue($managementRouter->is_management);
    }
}