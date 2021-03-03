<?php

namespace App\Jobs\Sync;

use App\Jobs\Job;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Completed extends Job
{
    private $model;

    protected $syncDelete;

    public function __construct(Model $model, $syncDelete = false)
    {
        $this->model = $model;
        $this->syncDelete = $syncDelete;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);
        $this->model->setSyncCompleted();

        if ($this->syncDelete) {
            $this->model->syncDelete();
        }
        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
