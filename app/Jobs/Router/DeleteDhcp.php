<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteDhcp extends Job
{
    use Batchable, LoggableModelJob;

    const TASK_WAIT_DATA_KEY = 'sync_router_delete_dhcp_task_id';

    private Router $model;
    private Task $task;

    public function __construct(Task $task, Router $router)
    {
        $this->task = $task;
        $this->model = $router;
    }

    public function handle()
    {
        $availabilityZone = $this->model->availabilityZone;
        $vpc = $this->model->vpc;

        if (!$vpc->dhcps()->where('availability_zone_id', $availabilityZone->id)->exists()) {
            Log::warning("DHCP doesn't exist in AZ, skipping");
            return;
        }

        if ($vpc->routers()->where('availability_zone_id', $availabilityZone->id)->count() != 1) {
            Log::warning("Other routers exist in AZ, skipping");
            return;
        }

        $deleteTask = $vpc->dhcps()->where('availability_zone_id', $availabilityZone->id)->get()->first()->syncDelete();

        $data = $this->task->data ?? [];
        $data[self::TASK_WAIT_DATA_KEY] = $deleteTask->id;

        $this->task->data = $data;
        $this->task->save();
    }
}
