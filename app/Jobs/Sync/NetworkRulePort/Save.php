<?php

namespace App\Jobs\Sync\NetworkRulePort;

use App\Jobs\Job;
use App\Jobs\Nsx\NetworkRulePort\Deploy;
use App\Jobs\Nsx\NetworkRulePort\DeployCheck;
use App\Models\V2\NetworkRulePort;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(NetworkRulePort $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $jobs = [
            new Deploy($this->model),
            new DeployCheck($this->model),
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
