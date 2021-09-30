<?php
namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CreateManagementNetwork extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private Task $task;
    private Router $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $router = $this->model;
        if (!empty($this->task->data['management_router_id'])) {
            // need to check that the router is up and running
            $managementRouter = Router::findOrFail($this->task->data['management_router_id']);
            if ($managementRouter) {
                $this->awaitSyncableResources([
                    $managementRouter->id,
                ]);
            }
            if (empty($this->task->data['management_network_id'])) {
                Log::info(get_class($this) . ' - Create Management Network Start', ['network_id' => $managementRouter->id]);

                $managementNetwork = app()->make(Network::class);
                $managementNetwork->name = 'Management Network for ' . $managementRouter->id;
                $managementNetwork->router_id = $managementRouter->id; // needs to be the management router
                $managementNetwork->subnet = $router->vpc->advanced_networking ?
                    config('network.subnet.advanced') :
                    config('network.subnet.standard');
                $managementNetwork->syncSave();

                // Store the management network id, so we can backoff everything else
                $this->task->data = Arr::add($this->task->data, 'management_network_id', $managementNetwork->id);
                $this->task->saveQuietly();

                Log::info(get_class($this) . ' - Create Management Network End', [
                    'router_id' => $managementRouter->id,
                    'admin_network_id' => $managementNetwork->id,
                ]);
            } else {
                $managementNetwork = Network::findOrFail($this->task->data['management_network_id']);
            }

            if ($managementNetwork) {
                $this->awaitSyncableResources([
                    $managementNetwork->id,
                ]);
            }
        }
    }
}
