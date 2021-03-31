<?php

namespace App\Jobs\Nsx\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable;

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

        $this->model->deleted = time();
        $this->model->saveQuietly();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
