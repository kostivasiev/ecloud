<?php

namespace App\Jobs\Sync\Volume;

use App\Jobs\Job;
use App\Jobs\VmWare\Volume\Undeploy;
use App\Jobs\VmWare\Volume\UndeployCheck;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    /** @var Volume */
    private $model;

    public function __construct(Volume $model)
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
