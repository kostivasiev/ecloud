<?php
namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteManagementNetworks extends Job
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
        $managementNetworkIds = [];
        if (empty($this->task->data['management_network_ids'])) {
            $this->model->routers->where('is_hidden', '=', true)->each(function ($router) use (&$managementNetworkIds) {
                Network::whereHas('router', function ($query) use ($router) {
                    $query->where('router_id', '=', $router->id);
                })->each(function ($network) use (&$managementNetworkIds) {
                    $network->syncDelete();
                    $managementNetworkIds[] = $network->id;
                });
            });
            $this->task->data = [
                'management_network_ids' => $managementNetworkIds,
            ];
            $this->task->saveQuietly();
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
