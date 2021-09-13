<?php
namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Jobs\Tasks\Instance\VolumeDetach;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class VolumeGroupDetach extends Job
{
    use Batchable, LoggableModelJob, AwaitTask;

    public $tries = 60;
    public $backoff = 5;

    private Instance $model;
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    public function handle()
    {
        $instance = $this->model;
        if (!empty($instance->volume_group_id)) {
            Log::info(
                'Instance is not associated with a volume group, skipping',
                [
                    'instance_id' => $instance->id,
                ]
            );
            return;
        }
        $instance->volumes()->where('is_shared', '=', true)
            ->each(function ($volume) use ($instance) {
                if (isset($this->task->data['volume_detach_task_id'])) {
                    $task = Task::findOrFail($this->task->data['volume_detach_task_id']);
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
                $this->task->setAttribute('data', ['volume_detach_task_id' => $task->id])->saveQuietly();
                $this->awaitTaskWithRelease($task);
            });
    }
}
