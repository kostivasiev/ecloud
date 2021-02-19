<?php

namespace App\Jobs\Sync\Volume;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\CapacityChange;
use App\Jobs\Kingpin\Volume\Deploy;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Jobs\Kingpin\Volume\MarkSyncCompleted;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;
    private $originalValues;

    public function __construct(Volume $model, $originalValues)
    {
        $this->model = $model;
        $this->originalValues = $originalValues;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $volume = $this->model;

        $jobs = [
            new Deploy($this->model),
        ];

        dump($this->originalValues);
        dump($volume->attributesToArray());

        if (!isset($this->originalValues['iops']) || $this->originalValues['iops'] !== $volume->iops) {
            $jobs[] = new IopsChange($this->model);
        }

        if (!isset($this->originalValues['capacity']) || $this->originalValues['capacity'] !== $volume->capacity) {
            $jobs[] = new CapacityChange($this->model);
        }

        $jobs[] = new MarkSyncCompleted($this->model);

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
