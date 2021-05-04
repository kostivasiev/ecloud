<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitFirewallPolicyRemoval extends Job
{
    use Batchable, JobModel;

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
            $this->model->firewallPolicies()->each(function ($fwp) {
                if ($fwp->sync->status == Sync::STATUS_FAILED) {
                    Log::error('Firewall policy in failed sync state, abort', ['id' => $this->model->id, 'fwp' => $fwp->id]);
                    $this->fail(new \Exception("Firewall policy '" . $fwp->id . "' in failed sync state"));
                    return;
                }
            });

            Log::warning($this->model->firewallPolicies()->count() . ' firewall polic(y/ies) still attached, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id]);
            return $this->release($this->backoff);
        }
    }
}
