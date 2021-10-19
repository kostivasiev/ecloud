<?php
namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateManagementRouter extends Job
{
    use Batchable, LoggableModelJob, AwaitResources, AwaitTask;

    private Task $task;
    private Router $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $router = $this->model;
        $managementRouter = null;
        if (empty($this->task->data['management_router_id'])) {
            $managementCount = $router->vpc->routers()->where(function ($query) use ($router) {
                $query->where('is_hidden', '=', true);
                $query->where('availability_zone_id', '=', $router->availability_zone_id);
            })->count();
            if ($managementCount == 0) {
                Log::info(get_class($this) . ' - Create Management Router Start', ['router_id' => $router->id]);

                $managementRouter = app()->make(Router::class);
                $managementRouter->vpc_id = $router->vpc_id;
                $managementRouter->name = 'Management Router for ' . $router->availability_zone_id . ' - ' . $router->vpc_id;
                $managementRouter->availability_zone_id = $router->availability_zone_id;
                $managementRouter->is_hidden = true;
                $managementRouter->syncSave();

                // Store the management router id, so we can backoff everything else
                $this->task->data = [
                    'management_router_id' => $managementRouter->id
                ];
                $this->task->saveQuietly();

                Log::info(get_class($this) . ' - Create Management Router End', [
                    'router_id' => $router->id,
                    'admin_router_id' => $managementRouter->id,
                ]);
            }
        } else {
            $managementRouter = Router::findOrFail($this->task->data['management_router_id']);
        }

        if ($managementRouter) {
            $this->awaitSyncableResources([
                $managementRouter->id,
            ]);
        }
    }
}
