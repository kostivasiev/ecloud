<?php
namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Models\V2\Router;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateManagementRouter extends TaskJob
{
    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $router = $this->task->resource;
        $managementRouter = null;
        if (empty($this->task->data['management_router_id'])) {
            $managementCount = $router->vpc->routers()->where(function ($query) use ($router) {
                $query->where('is_management', '=', true);
                $query->where('availability_zone_id', '=', $router->availability_zone_id);
            })->count();
            if ($managementCount == 0) {
                $this->info('Create Management Router Start');

                $managementRouter = app()->make(Router::class);
                $managementRouter->vpc_id = $router->vpc_id;
                $managementRouter->name = 'Management Router for ' . $router->availability_zone_id . ' - ' . $router->vpc_id;
                $managementRouter->availability_zone_id = $router->availability_zone_id;
                $managementRouter->is_management = true;
                $managementRouter->syncSave();

                // Store the management router id, so we can backoff everything else
                $this->task->updateData('management_router_id', $managementRouter->id);

                $this->info('Create Management Router End', [
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
