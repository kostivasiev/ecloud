<?php

namespace App\Jobs\Task\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $jobs = [
            new \App\Jobs\Kingpin\HostGroup\CreateCluster($this->model),
            new \App\Jobs\Nsx\HostGroup\CreateTransportNode($this->model),
            new \App\Jobs\Nsx\HostGroup\PrepareCluster($this->model),
            new \App\Jobs\Task\Completed($this->model),
        ];
        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setTaskFailureReason($exception->getMessage());
    }
}
