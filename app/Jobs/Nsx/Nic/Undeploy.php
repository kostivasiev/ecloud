<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\Job;
use App\Models\V2\Nic;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private $model;

    public function __construct(Nic $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        // TODO :- Undeploy

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
