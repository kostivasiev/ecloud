<?php

namespace App\Jobs\Volume;

use App\Jobs\Job;
use App\Jobs\Tasks\Instance\VolumeAttach;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AssignVolumeGroup extends Job
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
            $volume->volumeGroup->instances()->each(function ($instance) use ($volume) {
                if (!empty($this->task->data['instance_attach_task_id'])) {
                    $task = Task::findOrFail($this->task->data['instance_attach_task_id']);
                    if (!$task->completed) {
                        $this->awaitTaskWithRelease($task);
                    }
                    $this->task->updateData('instance_attach_task_id', null);
                    if ($instance->volumes()->where('id', '=', $volume->id)->count() > 0) {
                        Log::info(
                            'Volume is already associated with a volume group, skipping',
                            [
                                'instance_id' => $instance->id,
                                'volume_id' => $volume->id,
                            ]
                        );
                        return;
                    }
                }

                Log::info(
                    'Attaching volume to instance',
                    [
                        'instance_id' => $instance->id,
                        'volume_id' => $volume->id,
                    ]
                );

                $task = $instance->createTask('volume_attach', VolumeAttach::class, ['volume_id' => $volume->id]);
                $this->task->updateData('instance_attach_task_id', $task->id);
                $this->awaitTaskWithRelease($task);
            });
        }
    }
}
