<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitNicRemoval extends Job
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

        if ($this->instance->nics()->count() > 0) {
            $this->instance->nics()->each(function ($nic) {
                if ($nic->sync->status == Sync::STATUS_FAILED) {
                    Log::error('NIC in failed sync state, abort', ['id' => $this->instance->id, 'nic' => $nic->id]);
                    $this->fail(new \Exception("NIC '" . $nic->id . "' in failed sync state"));
                    return;
                }
            });

            Log::warning($this->instance->nics()->count() . ' NIC(s) still attached, retrying in ' . $this->backoff . ' seconds', ['id' => $this->instance->id]);
            $this->release($this->backoff);
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
