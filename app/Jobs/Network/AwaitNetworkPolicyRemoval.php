<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use App\Support\Sync;

class AwaitNetworkPolicyRemoval extends TaskJob
{
    public $tries = 30;
    public $backoff = 5;

    public function handle()
    {
        $network = $this->task->resource;

        if ($network->networkPolicy()->exists()) {
            if ($network->networkPolicy->sync->status == Sync::STATUS_FAILED) {
                $this->fail(new \Exception("Network policy '" . $network->networkPolicy->id . "' in failed sync state"));
                return;
            }

            $this->warning('Network ' . $network->id . ' still has an associated network policy, retrying in ' . $this->backoff . ' seconds');
            $this->release($this->backoff);
        }
    }
}
