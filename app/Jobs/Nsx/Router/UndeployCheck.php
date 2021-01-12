<?php

namespace App\Jobs\Nsx\Router;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    /** @var Router */
    private $model;

    public function __construct(Router $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        // TODO :- Undeploy Router

        $this->model->setSyncCompleted();
        $this->model->syncDelete();

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
