<?php

namespace App\Traits\V2;

use App\Jobs\Sync\TestSyncable\Test1;
use App\Jobs\Sync\TestSyncable\Test2;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\Sync;
use App\Models\V2\Volume;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

trait SyncableBatch
{
    public function syncBatchExceptionCallback()
    {
        return function (Throwable $e) {
            return $e->getMessage();
        };
    }

    public function updateSyncBatch($jobs)
    {
        $sync = $this->sync;
        $callback = $this->syncBatchExceptionCallback();

        return Bus::batch($jobs)->then(function (Batch $batch) use ($sync) {
            $sync->completed = true;
            $sync->save();
        })->catch(function (Batch $batch, Throwable $e) use ($sync, $callback) {
            $sync->failure_reason = $callback($e);
            $sync->save();
        });
    }

    public function deleteSyncBatch($jobs)
    {
        $sync = $this->sync;

        return $this->updateSyncBatch($jobs)->then(function (Batch $batch) use ($sync) {
            $sync->resource->delete();
        });
    }
}
