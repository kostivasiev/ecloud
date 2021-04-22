<?php

namespace App\Jobs\Router\Defaults;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitFirewallPolicySync extends Job
{
    use Batchable;

    public $tries = 30;
    public $backoff = 5;

    private $firewallPolicy;

    public function __construct(FirewallPolicy $firewallPolicy)
    {
        $this->firewallPolicy = $firewallPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->firewallPolicy->id]);

        if ($this->firewallPolicy->sync->status == Sync::STATUS_FAILED) {
            Log::error('Firewall policy in failed sync state, abort', ['id' => $this->firewallPolicy->id]);
            $this->fail(new \Exception("Firewall policy '" . $this->firewallPolicy->id . "' in failed sync state"));
            return;
        }

        if ($this->firewallPolicy->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Firewall policy not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->firewallPolicy->id]);
            return $this->release($this->backoff);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->firewallPolicy->id]);
    }
}
