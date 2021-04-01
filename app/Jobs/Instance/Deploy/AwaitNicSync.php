<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitNicSync extends Job
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
            if ($nic->getStatus() == Sync::STATUS_FAILED) {
                Log::error('NIC in failed sync state, abort', ['id' => $this->instance->id, 'nic' => $nic->id]);
                $this->fail(new \Exception("NIC '" . $nic->id . "' in failed sync state"));
            }

            if ($nic->getStatus() != Sync::STATUS_COMPLETE) {
                Log::warning('NIC not in sync, retrying', ['id' => $this->instance->id, 'nic' => $nic->id]);
                throw new \Exception('Nic not in sync');
            }
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
