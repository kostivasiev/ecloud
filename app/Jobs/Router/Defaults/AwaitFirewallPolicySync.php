<?php

namespace App\Jobs\Router\Defaults;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitFirewallPolicySync extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 30;
    public $backoff = 5;

    private $model;

    public function __construct(FirewallPolicy $firewallPolicy)
    {
        $this->model = $firewallPolicy;
    }

    public function handle()
    {
        if ($this->model->sync->status == Sync::STATUS_FAILED) {
            Log::error('Firewall policy in failed sync state, abort', ['id' => $this->model->id]);
            $this->fail(new \Exception("Firewall policy '" . $this->model->id . "' in failed sync state"));
            return;
        }

        if ($this->model->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Firewall policy not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id]);
            return $this->release($this->backoff);
        }
    }
}
