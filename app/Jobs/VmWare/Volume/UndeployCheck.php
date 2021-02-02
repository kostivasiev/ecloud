<?php

namespace App\Jobs\VmWare\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    /** @var Volume */
    private $model;

    public function __construct(Volume $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        // TODO :- Undeploy Volume

        $this->model->setSyncCompleted();
        $this->model->syncDelete();

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
