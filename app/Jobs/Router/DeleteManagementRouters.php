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

class DeleteManagementRouters extends Job
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
        if (empty($this->task->data['management_router_ids'])) {
            $managementRoutersIds = [];
            $this->model->routers->where('is_management', '=', true)->each(function ($router) use (&$managementRoutersIds) {
                $router->syncDelete();
                $managementRoutersIds[] = $router->id;
            });

            $this->task->data = [
                'management_router_ids' => $managementRoutersIds,
            ];
            $this->task->saveQuietly();
        } else {
            $managementRoutersIds = Router::whereIn('id', $this->task->data['management_router_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }

        if ($managementRoutersIds) {
            $this->awaitSyncableResources($managementRoutersIds);
        }
    }
}
