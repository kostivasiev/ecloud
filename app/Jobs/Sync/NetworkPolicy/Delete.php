<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\Nsx\NetworkPolicy\Undeploy;
use App\Jobs\Nsx\NetworkPolicy\UndeployCheck;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Log;

class Delete extends Job
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
            new Undeploy($this->model),
            new UndeployCheck($this->model)
        ];
        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
