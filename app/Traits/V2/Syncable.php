<?php

namespace App\Traits\V2;

use App\Exceptions\SyncException;
use App\Listeners\V2\ResourceSyncSaving;
use App\Models\V2\Sync;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

trait Syncable
{
    public function syncs()
    {
        return $this->morphMany(Sync::class, 'resource');
    }

    // TODO: Make this abstract - we should force objects implementing Syncable to return job class
    public function getUpdateSyncJob()
    {
        $class = explode('\\', __CLASS__);
        return 'App\\Jobs\\Sync\\' . end($class) . '\\Update';
    }

    // TODO: Make this abstract - we should force objects implementing Syncable to return job class
    public function getDeleteSyncJob()
    {
        $class = explode('\\', __CLASS__);
        return 'App\\Jobs\\Sync\\' . end($class) . '\\Delete';
    }

    public function withSyncLock($callback)
    {
        $lock = Cache::lock($this->syncGetLockKey(), 60);

        try {
            Log::debug(get_class($this) . ' : Attempting to obtain sync lock for 60s', ['resource_id' => $this->id]);
            $lock->block(60);

            $callback($this);
        } finally {
            $lock->release();
        }
    }

    public function syncGetLockKey()
    {
        return 'sync.' . $this->id;
    }

    public function canSync()
    {
        if ($this->sync->status === Sync::STATUS_INPROGRESS) {
            Log::warning(get_class($this) . ' : Cannot sync, resource has sync in progress', ['resource_id' => $this->id]);
            return false;
        }

        return true;
    }

    public function createSync($type = Sync::TYPE_UPDATE)
    {
        Log::info(get_class($this) . ' : Creating new sync - Started', [
            'resource_id' => $this->id,
        ]);

        $sync = app()->make(Sync::class);
        $sync->resource()->associate($this);
        $sync->completed = false;
        $sync->type = $type;
        $sync->save();

        Log::info(get_class($this) . ' : Creating new sync - Finished', [
            'resource_id' => $this->id,
        ]);

        return $sync;
    }

    public function getSyncAttribute()
    {
        $status = 'unknown';
        $type = 'unknown';

        if ($this->syncs()->count()) {
            $latest = $this->syncs()->latest()->first();
            $status = $latest->status;
            $type = $latest->type;
        }

        return (object) [
            'status' => $status,
            'type' => $type,
        ];
    }

    // TODO: Remove this once all models are using new sync
    public function setSyncCompleted()
    {
        Log::info(get_class($this) . ' : Setting Sync to completed - Started', ['resource_id' => $this->id]);
        if (!$this->syncs()->count()) {
            Log::info(
                get_class($this) . ' : Setting Sync to completed - Not found, skipped',
                ['resource_id' => $this->id]
            );
            return;
        }
        $sync = $this->syncs()->latest()->first();
        $sync->completed = true;
        $sync->save();
        Log::info(get_class($this) . ' : Setting Sync to completed - Finished', ['resource_id' => $this->id]);
    }

    // TODO: Remove this once all models are using new sync
    public function setSyncFailureReason($value)
    {
        Log::info(get_class($this) . ' : Setting Sync to failed - Started', ['resource_id' => $this->id]);
        if (!$this->syncs()->count()) {
            return;
        }
        $sync = $this->syncs()->latest()->first();
        $sync->failure_reason = $value;
        $sync->save();
        Log::debug(get_class($this), ['reason' => $value]);
        Log::info(get_class($this) . ' : Setting Sync to failed - Finished', ['resource_id' => $this->id]);
    }

    /**
     * TODO :- move this to exception handler to handle exception thrown from
     *         ResourceSyncSaving/ResourceSyncDeleting event listeners
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSyncError()
    {
        return response()->json(
            [
                'errors' => [
                    [
                        'title' => 'Resource unavailable',
                        'detail' => 'The specified resource is being modified and is unavailable at this time',
                        'status' => Response::HTTP_CONFLICT,
                    ],
                ],
            ],
            Response::HTTP_CONFLICT
        );
    }
}
