<?php

namespace App\Jobs\Sync\TestSyncable;

use App\Jobs\Job;
use App\Jobs\Kingpin\Volume\CapacityChange;
use App\Jobs\Kingpin\Volume\Deploy;
use App\Jobs\Kingpin\Volume\IopsChange;
use App\Jobs\Sync\Completed;
use App\Models\V2\Sync;
use App\Models\V2\TestSyncable;
use App\Models\V2\Volume;
use App\Traits\V2\SyncableBatch;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batchable;
use Throwable;

class Update extends Job
{
    use SyncableBatch;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['sync_id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);

        $this->updateSyncBatch([
            [
                new Test1($this->sync),
                new Test2($this->sync),
            ]
        ])->dispatch();

        //$this->fail(new \Exception("thrown a test exception via insta fail"));

        Log::info(get_class($this) . ' : Finished', ['sync_id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}
