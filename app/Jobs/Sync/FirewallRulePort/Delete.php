<?php

namespace App\Jobs\Sync\FirewallRulePort;

use App\Jobs\Job;
use App\Models\V2\FirewallRulePort;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    /** @var FirewallRulePort */
    private $model;

    public function __construct(FirewallRulePort $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

//        $jobs = [
//            new Undeploy($this->model),
//            new UndeployCheck($this->model),
//        ];
//
//        dispatch(array_shift($jobs)->chain($jobs));

        // TODO - MOVE TO UNDEPLOY CHECK JOB
        $this->model->syncDelete();
        $this->model->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
