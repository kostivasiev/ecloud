<?php

namespace App\Jobs\Volume;

use App\Jobs\Job;
use App\Jobs\Tasks\Instance\VolumeDetach;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UnAssignVolumeGroup extends Job
{
    use Batchable, LoggableModelJob, AwaitTask;

    public $tries = 60;
    public $backoff = 5;

    private Task $task;
    private Volume $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $volume = $this->model;
        if (!empty($volume->volume_group_id)) {
            Log::info(
                'Volume is not associated with a volume group, skipping',
                [
                    'volume_id' => $volume->id,
                ]
            );
            return;
        }

        Instance::whereHas('volumes', function ($query) use ($volume) {
            $query->where('id', '=', $volume->id);
        })->each(function ($instance) use ($volume) {
            if (isset($this->task->data['instance_detach_task_id'])) {
                $task = Task::findOrFail($this->task->data['instance_detach_task_id']);
                if (!$task->completed) {
                    $this->awaitTaskWithRelease($task);
                }
                $this->task->setAttribute('data', null)->saveQuietly();
            }

            Log::info(
                'Detaching volume from instance',
                [
                    'instance_id' => $instance->id,
                    'volume_id' => $volume->id,
                ]
            );

            $task = $instance->createTask('volume_detach', VolumeDetach::class, ['volume_id' => $volume->id]);
            $this->task->setAttribute('data', ['instance_detach_task_id' => $task->id]);
            $this->awaitTaskWithRelease($task);
        });
    }
}
