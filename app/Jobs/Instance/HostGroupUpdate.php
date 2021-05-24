<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Traits\V2\LoggableModelJob;
use App\Traits\V2\TaskableBatch;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class HostGroupUpdate extends Job
{
    use Batchable, TaskableBatch, LoggableModelJob;

    public Task $task;
    private $model;
    private $host_group_id;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
        $this->host_group_id = (isset($this->task->data['host_group_id'])) ? $this->task->data['host_group_id'] : null;
    }

    public function handle()
    {
        if (($this->model->host_group_id == $this->host_group_id) || (empty($this->host_group_id))) {
            Log::info(get_class($this) . ' : Finished: No changes required', ['id' => $this->model->id]);
            return;
        }

        $originalHostGroup = HostGroup::findOrFail($this->host_group_id);
        $newHostGroup = HostGroup::findOrFail($this->model->host_group_id);
        $cyclePower = ($originalHostGroup->hostSpec->id != $newHostGroup->hostSpec->id);

        $jobs = [
            new MoveToHostGroup($this->model),
        ];
        if ($cyclePower) {
            array_unshift($jobs, new PowerOff($this->model));
            array_push($jobs, new PowerOn($this->model));
        }

        $this->updateTaskBatch([$jobs])->dispatch();
    }
}
