<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CheckNetworkAvailable extends Job
{
    use Batchable;

    public $tries = 30;
    public $backoff = 5;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $network = Network::findOrFail($this->instance->deploy_data['network_id']);

        if ($network->sync->status == Sync::STATUS_FAILED) {
            $this->fail(new \Exception("Network '" . $network->id . "' in failed sync state"));
            return;
        }

        if ($network->sync->status == Sync::STATUS_INPROGRESS) {
            Log::warning('Network not in sync, retrying in ' . $this->backoff . ' seconds', [
                'id' => $this->instance->id,
                'network' => $network->id
            ]);
            $this->release($this->backoff);
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
