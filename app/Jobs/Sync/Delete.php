<?php

namespace App\Jobs\Sync;

use App\Jobs\Job;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    private $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);
        $this->model->syncDelete();
        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}