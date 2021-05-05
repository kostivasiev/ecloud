<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Support\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitVolumeRemoval extends Job
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

        if ($this->instance->volumes()->count() > 0) {
            $this->instance->volumes()->each(function ($volume) {
                if ($volume->sync->status == Sync::STATUS_FAILED) {
                    Log::error('Volume in failed sync state, abort', ['id' => $this->instance->id, 'volume' => $volume->id]);
                    $this->fail(new \Exception("Volume '" . $volume->id . "' in failed sync state"));
                    return;
                }
            });

            Log::warning($this->instance->volumes()->count() . ' Volume(s) still attached, retrying in ' . $this->backoff . ' seconds', ['id' => $this->instance->id]);
            $this->release($this->backoff);
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
