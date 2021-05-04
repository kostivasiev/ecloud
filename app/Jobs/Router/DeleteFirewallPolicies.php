<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteFirewallPolicies extends Job
{
    use Batchable, JobModel;

    private Router $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    public function handle()
    {
        $this->model->firewallPolicies()->each(function ($fwp) {
            $fwp->delete();
        });
    }
}
