<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $jobs = [
            new \App\Jobs\Kingpin\Host\CheckExists($this->model),
            new \App\Jobs\Kingpin\Host\MaintenanceMode($this->model),
            new \App\Jobs\Conjurer\Host\PowerOff($this->model),
//            new \App\Jobs\Artisan\Host\RemoveFrom3Par($this->model),
            new \App\Jobs\Kingpin\Host\RemoveFromHostGroup($this->model),
            new \App\Jobs\Conjurer\Host\DeleteServiceProfile($this->model),
            new \App\Jobs\Sync\Completed($this->model),
            new \App\Jobs\Sync\Delete($this->model),
        ];
        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
