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
    protected $syncLockOwner = null;

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

    public function syncLock()
    {
        Log::debug("syncLock : Attempting to obtain previous lock owner", ['resource_id' => $this->id]);
        if ($this->syncLockOwner) {
            Log::debug("syncLock : Restoring lock from owner", ['resource_id' => $this->id, 'lock_owner' => $this->syncLockOwner]);
            return Cache::restoreLock('sync.' . $this->id, $this->syncLockOwner);
        }

        Log::debug("syncLock : Attempting to obtain lock for 60s", ['resource_id' => $this->id]);
        $lock = Cache::lock('sync.' . $this->id, 60);
        $lock->block(60);

        $this->syncLockOwner = $lock->owner();

        return $lock;
    }

    public function canSync($type = Sync::TYPE_UPDATE)
    {
        if ($type == Sync::TYPE_UPDATE) {
            if ($this->syncs()->count() == 1 && $this->sync->status === Sync::STATUS_FAILED) {
                Log::warning(get_class($this) . ' : Cannot sync, resource has a single failed sync', ['resource_id' => $this->id]);
                return false;
            }
        }

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

        try {
            $lock = $this->syncLock();

            if (!$this->canSync($type)) {
                throw new SyncException("Cannot sync");
            }

            $sync = app()->make(Sync::class);
            $sync->resource()->associate($this);
            $sync->completed = false;
            $sync->type = $type;
            $sync->save();
        } finally {
            $lock->release();
        }

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
