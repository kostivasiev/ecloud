<?php
namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Jobs\Tasks\Instance\VolumeDetach;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DetachSharedVolumes extends Job
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
        $this->model->volumes()->where('is_shared', '=', true)
            ->each(function ($volume) {
                if (!empty($this->task->data['volume_detach_task_id'])) {
                    $task = Task::findOrFail($this->task->data['volume_detach_task_id']);
                    if (!$task->completed) {
                        $this->awaitTaskWithRelease($task);
                    }
                    $this->task->updateData('volume_detach_task_id', null);
                }

                Log::info('Detaching volume from instance', ['instance_id' => $this->model->id, 'volume_id' => $volume->id]);
                $task = $this->model->createTask('volume_detach', VolumeDetach::class, ['volume_id' => $volume->id]);
                $this->task->updateData('volume_detach_task_id', $task->id);
                $this->awaitTaskWithRelease($task);
            });
    }
}
