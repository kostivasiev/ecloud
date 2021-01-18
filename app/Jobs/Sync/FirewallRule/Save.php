<?php

namespace App\Jobs\Sync\FirewallRule;

use App\Jobs\Job;
use App\Jobs\Nsx\FirewallPolicy\Deploy;
use App\Jobs\Nsx\FirewallPolicy\DeployCheck;
use App\Models\V2\FirewallRule;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(FirewallRule $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        $jobs = [
            new Deploy($this->model->firewallPolicy),
            new DeployCheck($this->model->firewallPolicy),
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
