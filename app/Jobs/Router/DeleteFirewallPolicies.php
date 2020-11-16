<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class DeleteFirewallPolicies extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);
        $router = Router::withTrashed()->findOrFail($this->data['router_id']);
        $router->firewallPolicies()->each(function ($policy) {
            $policy->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
