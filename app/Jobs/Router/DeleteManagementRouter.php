<?php
namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteManagementRouter extends Job
{
    use Batchable, LoggableModelJob, AwaitResources, AwaitTask;

    private Task $task;
    private Vpc $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        if (empty($this->task->data['management_router_id'])) {
            $managementRouter = $this->model->routers->where('is_hidden', '=', true)->first();
            if ($managementRouter) {
                $this->task->setAttribute('data', ['management_router_id' => $managementRouter->id])->saveQuietly();
                $managementRouter->syncDelete();
            }
        } else {
            $managementRouter = Router::find($this->task->data['management_router_id']);
        }

        if ($managementRouter) {
            $this->awaitSyncableResources([
                $managementRouter->id,
            ]);
        }
    }
}
