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
        $managementNetwork = null;
        if (empty($this->task->data['management_network_id'])) {
            $router = $this->model->routers->where('is_hidden', '=', true)->first();
            if ($router) {
                $managementNetwork = Network::whereHas('router', function ($query) use ($router) {
                    $query->where('router_id', '=', $router->id);
                })->first();
                $this->task->setAttribute('data', ['management_network_id' => $managementNetwork->id])->saveQuietly();
                $managementNetwork->syncDelete();
            }
        } else {
            $managementNetwork = Network::find($this->task->data['management_network_id']);
        }

        if ($managementNetwork) {
            $this->awaitSyncableResources([
                $managementNetwork->id,
            ]);
        }
    }
}
