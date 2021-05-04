<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Support\Sync;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitRouterSync extends Job
{
    use Batchable, JobModel;

    public $tries = 60;
    public $backoff = 10;

    private $model;

    public function __construct(Network $network)
    {
        $this->model = $network;
    }

    public function handle()
    {
        if ($this->model->router->sync->status == Sync::STATUS_FAILED) {
            Log::error('Router in failed sync state, abort', ['id' => $this->model->id]);
            $this->fail(new \Exception("Router '" . $this->model->router->id . "' in failed sync state"));
            return;
        }

        if ($this->model->router->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Router not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id]);
            return $this->release($this->backoff);
        }
    }
}
