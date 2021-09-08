<?php
namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Jobs\Tasks\Instance\VolumeAttach;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class VolumeGroupAttach extends Job
{
    use Batchable, LoggableModelJob, AwaitTask;

    public $tries = 60;
    public $backoff = 5;

    private Task $task;
    private Instance $model;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $instance = $this->model;

        if (!empty($instance->volume_group_id)) {
            $instance->volumeGroup->volumes()->each(function ($volume) use ($instance) {
                if (isset($this->task->data['volume_attach_task_id'])) {
                    $task = Task::findOrFail($this->task->data['volume_attach_task_id']);
                    if (!$task->completed) {
                        $this->awaitTaskWithRelease($task);
                    }
                    $this->task->setAttribute('data', null)->saveQuietly();
                }
                if ($instance->volumes()->where('id', '=', $volume->id)->count() > 0) {
                    Log::info(
                        'Volume is already mounted on Instance, skipping',
                        [
                            'instance_id' => $instance->id,
                            'volume_id' => $volume->id,
                        ]
                    );
                    return;
                }

                Log::info(
                    'Attaching volume to instance',
                    [
                        'instance_id' => $instance->id,
                        'volume_id' => $volume->id,
                    ]
                );

                $task = $instance->createTask('volume_attach', VolumeAttach::class, ['volume_id' => $volume->id], true);
                $this->task->setAttribute('data', ['volume_attach_task_id' => $task->id])->saveQuietly();
                $this->awaitTaskWithRelease($task);
            });
        }
    }
}
