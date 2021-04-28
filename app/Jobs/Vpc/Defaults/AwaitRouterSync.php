<?php

namespace App\Jobs\Vpc\Defaults;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use Illuminate\Support\Facades\Log;

class AwaitRouterSync extends Job
{
    public $tries = 60;
    public $backoff = 10;

    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->router->id]);

        if ($this->router->sync->status == Sync::STATUS_FAILED) {
            Log::error('Router in failed sync state, abort', ['id' => $this->router->id]);
            $this->fail(new \Exception("Router '" . $this->router->id . "' in failed sync state"));
            return;
        }

        if ($this->router->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Router not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->router->id]);
            return $this->release($this->backoff);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->router->id]);
    }
}
