<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitNicRemoval extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 30;
    public $backoff = 5;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        if ($this->model->nics()->count() > 0) {
            $this->model->nics()->each(function ($nic) {
                if ($nic->sync->status == Sync::STATUS_FAILED) {
                    Log::error('NIC in failed sync state, abort', ['id' => $this->model->id, 'nic' => $nic->id]);
                    $this->fail(new \Exception("NIC '" . $nic->id . "' in failed sync state"));
                    return;
                }
            });

            Log::warning($this->model->nics()->count() . ' NIC(s) still attached, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id]);
            $this->release($this->backoff);
            return;
        }
    }
}
