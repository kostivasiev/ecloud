<?php

namespace App\Jobs\Sync\Nat;

use App\Jobs\Job;
use App\Jobs\Nsx\Nat\Undeploy;
use App\Jobs\Nsx\Nat\UndeployCheck;
use App\Models\V2\Nat;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    /** @var Nat */
    private $model;

    public function __construct(Nat $model)
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
