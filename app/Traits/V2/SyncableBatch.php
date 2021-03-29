<?php

namespace App\Traits\V2;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

trait SyncableBatch
{
    public function syncBatchExceptionCallback()
    {
        return function (Throwable $e) {
            return ($e instanceof RequestException && $e->hasResponse()) ?
                $e->getResponse()->getBody()->getContents() :
                $e->getMessage();
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
