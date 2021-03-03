<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\Sync\Completed;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(NetworkPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $jobs = [
            new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Deploy($this->model),
            new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\DeployCheck($this->model),
            new \App\Jobs\Nsx\NetworkPolicy\Deploy($this->model),
            new \App\Jobs\Nsx\NetworkPolicy\DeployCheck($this->model),
            new Completed($this->model)
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
