<?php

namespace App\Jobs\Sync\Network;

use App\Jobs\Job;
use App\Jobs\Nsx\Network\Undeploy;
use App\Jobs\Nsx\Network\UndeployCheck;
use App\Models\V2\Network;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    private $model;

    public function __construct(Network $model)
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
