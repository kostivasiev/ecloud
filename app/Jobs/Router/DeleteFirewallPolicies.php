<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteFirewallPolicies extends Job
{
    use Batchable, LoggableModelJob;

    private Router $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    public function handle()
    {
        $this->model->firewallPolicies()->each(function ($fwp) {
            $fwp->syncDelete();
        });
    }
}
