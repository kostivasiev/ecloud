<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitFirewallPolicyRemoval extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 30;
    public $backoff = 5;

    private Router $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    public function handle()
    {
        if ($this->model->firewallPolicies()->count() > 0) {
            foreach ($this->model->firewallPolicies as $firewallPolicy) {
                if ($firewallPolicy->sync->status == Sync::STATUS_FAILED) {
                    $this->fail(new \Exception("Firewall policy '" . $firewallPolicy->id . "' in failed sync state"));
                    return;
                }
            }

            Log::warning($this->model->firewallPolicies()->count() . ' firewall polic(y/ies) still attached, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id]);
            return $this->release($this->backoff);
        }
    }
}
