<?php

namespace App\Jobs\Sync\Instance;

use App\Jobs\Job;
use App\Jobs\Kingpin\Instance\Undeploy;
use App\Jobs\Kingpin\Instance\UndeployCheck;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    /** @var Instance */
    private $model;

    public function __construct(Instance $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $jobs = [
            new Undeploy($this->model),
            new UndeployCheck($this->model),
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
