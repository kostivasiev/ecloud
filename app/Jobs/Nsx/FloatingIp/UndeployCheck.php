<?php

namespace App\Jobs\Nsx\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(FloatingIp $model)
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
