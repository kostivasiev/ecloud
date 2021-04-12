<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
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

        $this->instance->nics()->each(function ($nic) {
            if ($nic->network->sync->status == Sync::STATUS_FAILED) {
                Log::error('Network in failed sync state, abort', [
                    'id' => $this->instance->id,
                    'nic' => $nic->id,
                    'network' => $nic->network->id
                ]);
                $this->fail(new \Exception("Network '" . $nic->network->id . "' in failed sync state"));
            }

            if ($nic->network->sync->status != Sync::STATUS_COMPLETE) {
                Log::warning('Network not in sync, retrying in ' . $this->backoff . ' seconds', [
                    'id' => $this->instance->id,
                    'nic' => $nic->id,
                    'network' => $nic->network->id
                ]);
                return $this->release($this->backoff);
            }
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
