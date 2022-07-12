<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitHostGroup extends Job
{
    use Batchable, LoggableModelJob;

    public int $tries = 60;

    public int $backoff = 5;

    private Instance $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        if (!$this->model->hostGroup()->exists()) {
            Log::info($this::class . ' : Waiting for host group allocation to instance ' . $this->model->id);
            $this->release($this->backoff);
            return;
        }

        Log::info($this::class . ' : Host group ' . $this->model->host_group_id . ' was allocated to instance ' . $this->model->id);
    }
}
