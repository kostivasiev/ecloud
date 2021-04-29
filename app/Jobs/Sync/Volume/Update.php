<?php

namespace App\Jobs\Sync\Volume;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\CapacityChange;
use App\Jobs\Kingpin\Volume\Deploy;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Models\V2\Task;
use App\Traits\V2\TaskableBatch;
use Illuminate\Support\Facades\Log;

class Update extends Job
{
    use TaskableBatch;

    private $task;
    private $originalValues;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->originalValues = $task->resource->getOriginal();
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);

        $volume = $this->task->resource;

        $jobs = [
            new Deploy($volume),
        ];

        // DO NOT DO THIS! Original values will be removed in the future!
        if (isset($this->originalValues['iops']) && $this->originalValues['iops'] != $volume->iops) {
            $jobs[] = new IopsChange($volume);
        }

        // DO NOT DO THIS! Original values will be removed in the future!
        if (isset($this->originalValues['capacity']) && $this->originalValues['capacity'] != $volume->capacity) {
            $jobs[] = new CapacityChange($volume);
        }

        $this->updateTaskBatch([
            $jobs
        ])->dispatch();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->task->id, 'resource_id' => $this->task->resource->id]);
    }
}
