<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;
use App\Support\Sync;

class AwaitRouterSync extends TaskJob
{
    public $tries = 60;
    public $backoff = 10;

    public function handle()
    {
        $network = $this->task->resource;

        if ($network->router->sync->status == Sync::STATUS_FAILED) {
            $this->error('Router ' . $network->router->id . ' in failed sync state, abort');
            $this->fail(new \Exception("Router '" . $network->router->id . "' in failed sync state"));
            return;
        }

        if ($network->router->sync->status != Sync::STATUS_COMPLETE) {
            $this->warning('Router ' . $network->router->id . ' not in sync, retrying in ' . $this->backoff . ' seconds');
            $this->release($this->backoff);
        }
    }
}
