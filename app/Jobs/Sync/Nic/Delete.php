<?php

namespace App\Jobs\Sync\Nic;

use App\Jobs\Job;
use App\Jobs\Nsx\Nic\Undeploy;
use App\Jobs\Nsx\Nic\UndeployCheck;
use App\Models\V2\Nic;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    /** @var Nic */
    private $model;

    public function __construct(Nic $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        $jobs = [
            new Undeploy($this->model),
            new UndeployCheck($this->model),
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
