<?php

namespace App\Traits\V2;

use App\Models\V2\Sync;
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

    public function createSync($type = Sync::TYPE_UPDATE)
    {
        Log::info(get_class($this) . ' : Creating new sync - Started', [
            'resource_id' => $this->id,
        ]);

        if ($this->sync->status === Sync::STATUS_INPROGRESS) {
            Log::info(get_class($this) . ' : Failed creating new sync on ' . __CLASS__ . ' with an outstanding sync', [
                'resource_id' => $this->id,
            ]);
            return false;
        }

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
        $status = null;
        $type = null;

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

    // TODO: Remove this once floating ip using new sync
    public function getSyncFailureReason()
    {
        if (!$this->syncs()->count()) {
            return null;
        }
        return $this->syncs()->latest()->first()->failure_reason;
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
