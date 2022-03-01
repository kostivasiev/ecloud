<?php
namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Models\V2\Router;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteManagementRouters extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $vpc = $this->task->resource;

        if (empty($this->task->data['management_router_ids'])) {
            $managementRoutersIds = [];
            $vpc->routers->where('is_management', '=', true)->each(function ($router) use (&$managementRoutersIds) {
                $router->syncDelete();
                $managementRoutersIds[] = $router->id;
            });

            $this->task->updateData('management_router_ids', $managementRoutersIds);
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
