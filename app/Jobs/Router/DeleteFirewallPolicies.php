<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteFirewallPolicies extends Job
{
    use Batchable;

    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->router->id]);

        $this->router->firewallPolicies()->each(function ($fwp) {
            $fwp->delete();
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->router->id]);
    }
}
