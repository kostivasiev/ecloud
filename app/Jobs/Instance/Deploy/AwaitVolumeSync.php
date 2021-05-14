<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AwaitVolumeSync extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 60;
    public $backoff = 5;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $this->model->volumes()->each(function ($volume) {
            if ($volume->sync->status == Sync::STATUS_FAILED) {
                Log::error('Volume in failed sync state, abort', ['id' => $this->model->id, 'volume' => $volume->id]);
                $this->fail(new \Exception("Volume '" . $volume->id . "' in failed sync state"));
                return false;
            }

            if ($volume->sync->status != Sync::STATUS_COMPLETE) {
                Log::warning('Volume not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'volume' => $volume->id]);
                return $this->release($this->backoff);
            }
        });
    }
}
