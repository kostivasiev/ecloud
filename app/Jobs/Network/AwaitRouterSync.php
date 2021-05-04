<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Support\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitRouterSync extends Job
{
    use Batchable;

    public $tries = 60;
    public $backoff = 10;

    private $network;

    public function __construct(Network $network)
    {
        $this->network = $network;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->network->id]);

        if ($this->network->router->sync->status == Sync::STATUS_FAILED) {
            Log::error('Router in failed sync state, abort', ['id' => $this->network->id]);
            $this->fail(new \Exception("Router '" . $this->network->router->id . "' in failed sync state"));
            return;
        }

        if ($this->network->router->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Router not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->network->id]);
            return $this->release($this->backoff);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->network->id]);
    }
}
