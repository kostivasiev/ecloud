<?php

namespace App\Jobs\Sync;

use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

class SetSyncCompleted extends Job
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $this->model->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
