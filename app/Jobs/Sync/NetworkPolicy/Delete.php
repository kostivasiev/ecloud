<?php

namespace App\Jobs\Sync\NetworkPolicy;

use App\Jobs\Job;
use App\Jobs\NetworkPolicy\DeleteChildResources;
use App\Jobs\Sync\SetSyncCompleted;
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
            new DeleteChildResources($this->model),
            new \App\Jobs\Nsx\NetworkPolicy\Undeploy($this->model),
            new \App\Jobs\Nsx\NetworkPolicy\UndeployCheck($this->model),
            new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\Undeploy($this->model),
            new \App\Jobs\Nsx\NetworkPolicy\SecurityGroup\UndeployCheck($this->model),
            new SetSyncCompleted($this->model, true)
        ];
        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
