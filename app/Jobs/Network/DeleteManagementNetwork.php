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

class DeleteManagementNetwork extends Job
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
        $managementNetwork = [];
        if (empty($this->task->data['management_network_ids'])) {
            $this->model->routers->where('is_hidden', '=', true)->each(function ($router) use (&$managementNetwork) {
                Network::whereHas('router', function ($query) use ($router) {
                    $query->where('router_id', '=', $router->id);
                })->each(function ($network) use (&$managementNetwork) {
                    $network->syncDelete();
                    $managementNetwork[] = $network->id;
                });
            });
            $this->task->data = [
                'management_network_ids' => $managementNetwork,
            ];
            $this->task->saveQuietly();
        } else {
            $managementNetwork = Network::whereIn($this->task->data['management_network_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }

        if ($managementNetwork) {
            $this->awaitSyncableResources($managementNetwork);
        }
    }
}
