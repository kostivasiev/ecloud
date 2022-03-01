<?php
namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use App\Models\V2\Network;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteManagementNetworks extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $vpc = $this->task->resource;

        $managementNetworkIds = [];
        if (empty($this->task->data['management_network_ids'])) {
            $vpc->routers->where('is_management', '=', true)->each(function ($router) use (&$managementNetworkIds) {
                Network::whereHas('router', function ($query) use ($router) {
                    $query->where('router_id', '=', $router->id);
                })->each(function ($network) use (&$managementNetworkIds) {
                    $network->syncDelete();
                    $managementNetworkIds[] = $network->id;
                });
            });
            $this->task->updateData('management_network_ids', $managementNetworkIds);
        } else {
            $managementNetworkIds = Network::whereIn('id', $this->task->data['management_network_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }

        if ($managementNetworkIds) {
            $this->awaitSyncableResources($managementNetworkIds);
        }
    }
}
