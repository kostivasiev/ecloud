<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Sync;
use App\Models\V2\Vpc;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitDhcpRemoval extends Job
{
    use Batchable;

    public $tries = 30;
    public $backoff = 5;

    private $vpc;

    public function __construct(Vpc $vpc)
    {
        $this->vpc = $vpc;
    }

    public function handle()
    {
        Log::debug(get_class($this) . ' : Started', ['id' => $this->vpc->id]);

        if ($this->vpc->nics()->count() > 0) {
            $this->vpc->nics()->each(function ($nic) {
                if ($nic->getStatus() == Sync::STATUS_FAILED) {
                    Log::error('NIC in failed sync state, abort', ['id' => $this->vpc->id, 'nic' => $nic->id]);
                    $this->fail(new \Exception("NIC '" . $nic->id . "' in failed sync state"));
                }
            });

            Log::warning("'" . $this->vpc->nics()->count() . "' NICs still attached, retrying", ['id' => $this->vpc->id]);
            throw new \Exception("'" . $this->vpc->nics()->count() . "' NICs still attached");
        }

        Log::debug(get_class($this) . ' : Finished', ['id' => $this->vpc->id]);
    }
}
