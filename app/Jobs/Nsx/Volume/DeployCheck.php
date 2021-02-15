<?php

namespace App\Jobs\Nsx\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class DeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(Volume $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        if (!is_null($this->model->vmware_uuid)) {
            $this->model->setSyncCompleted();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
