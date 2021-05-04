<?php

namespace App\Jobs\Vpc\Defaults;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use App\Traits\V2\JobModel;
use Illuminate\Support\Facades\Log;

class AwaitRouterSync extends Job
{
    use JobModel;

    public $tries = 60;
    public $backoff = 10;

    private $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    public function handle()
    {
        if ($this->model->sync->status == Sync::STATUS_FAILED) {
            Log::error('Router in failed sync state, abort', ['id' => $this->model->id]);
            $this->fail(new \Exception("Router '" . $this->model->id . "' in failed sync state"));
            return;
        }

        if ($this->model->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Router not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id]);
            return $this->release($this->backoff);
        }
    }
}
