<?php

namespace App\Jobs\Sync;

use App\Jobs\Job;
use Illuminate\Bus\Batchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Completed extends Job
{

    use Batchable;

    private $model;

    public function __construct(Model $model)
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
