<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Support\Sync;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitNicSync extends Job
{
    use Batchable, JobModel;

    public $tries = 30;
    public $backoff = 5;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $this->model->nics()->each(function ($nic) {
            if ($nic->sync->status == Sync::STATUS_FAILED) {
                Log::error('NIC in failed sync state, abort', ['id' => $this->model->id, 'nic' => $nic->id]);
                $this->fail(new \Exception("NIC '" . $nic->id . "' in failed sync state"));
                return;
            }

            if ($nic->sync->status != Sync::STATUS_COMPLETE) {
                Log::warning('NIC not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'nic' => $nic->id]);
                return $this->release($this->backoff);
            }
        });
    }
}
