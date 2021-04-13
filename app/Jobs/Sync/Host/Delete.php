<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Job;
use App\Models\V2\Sync;
use App\Traits\V2\SyncableBatch;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    use SyncableBatch;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);

        $jobs = [
            new \App\Jobs\Kingpin\Host\CheckExists($this->model),
            new \App\Jobs\Kingpin\Host\MaintenanceMode($this->model),
            new \App\Jobs\Conjurer\Host\PowerOff($this->model),
            new \App\Jobs\Artisan\Host\RemoveFrom3Par($this->model),
            new \App\Jobs\Kingpin\Host\RemoveFromHostGroup($this->model),
            new \App\Jobs\Conjurer\Host\DeleteServiceProfile($this->model),
            new \App\Jobs\Sync\Completed($this->model),
            new \App\Jobs\Sync\Delete($this->model),
        ];
        dispatch(array_shift($jobs)->chain($jobs));

        $host = $this->sync->resource;

        // TODO
//        $this->deleteSyncBatch([
//        ])->dispatch();

        // TODO delete this when we implement deleteSyncBatch
        $this->sync->completed = true;
        $this->sync->save();
        $this->sync->resource->delete();

        Log::info(get_class($this) . ' : Finished', ['id' => $host->id]);
    }
}
