<?php

namespace App\Jobs\Sync\Router;

use App\Jobs\Job;
use App\Jobs\Nsx\Router\Undeploy;
use App\Jobs\Nsx\Router\UndeployCheck;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    /** @var Router */
    private $model;

    public function __construct(Router $model)
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

        // TODO :- Delete linked Networks and FirewallPolicies

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
