<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitNetworkPolicyRemoval extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 30;
    public $backoff = 5;

    private Network $model;

    public function __construct(Network $network)
    {
        $this->model = $network;
    }

    public function handle()
    {
        if ($this->model->networkPolicy()->count() > 0) {
            if ($this->model->networkPolicy->sync->status == Sync::STATUS_FAILED) {
                $this->fail(new \Exception("Network policy '" . $this->model->networkPolicy->id . "' in failed sync state"));
                return;
            }

            Log::warning('Network still has an associated network policy, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id]);
            return $this->release($this->backoff);
        }
    }
}
