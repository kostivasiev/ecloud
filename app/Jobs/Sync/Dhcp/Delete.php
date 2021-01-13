<?php

namespace App\Jobs\Sync\Dhcp;

use App\Jobs\Job;
use App\Jobs\Nsx\Dhcp\Undeploy;
use App\Jobs\Nsx\Dhcp\UndeployCheck;
use App\Models\V2\Dhcp;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    private $model;

    public function __construct(Dhcp $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        $jobs = [
            new Undeploy($this->model),
            new UndeployCheck($this->model)
        ];
        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
