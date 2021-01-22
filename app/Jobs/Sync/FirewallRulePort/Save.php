<?php

namespace App\Jobs\Sync\FirewallRulePort;

use App\Jobs\Job;
use App\Jobs\Nsx\FirewallPolicy\Deploy;
use App\Jobs\Nsx\FirewallPolicy\DeployCheck;
use App\Models\V2\FirewallRulePort;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(FirewallRulePort $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $jobs = [
            new Deploy($this->model->firewallRule->firewallPolicy),
            new DeployCheck($this->model->firewallRule->firewallPolicy),
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
