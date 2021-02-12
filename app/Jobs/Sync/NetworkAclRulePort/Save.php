<?php

namespace App\Jobs\Sync\NetworkAclRulePort;

use App\Jobs\Job;
use App\Jobs\Nsx\NetworkAclRulePort\Deploy;
use App\Jobs\Nsx\NetworkAclRulePort\DeployCheck;
use App\Models\V2\NetworkAclRulePort;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(NetworkAclRulePort $model)
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
