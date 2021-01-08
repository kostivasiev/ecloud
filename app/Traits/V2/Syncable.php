<?php

namespace App\Traits\V2;

use App\Models\V2\Sync;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

trait Syncable
{
    public function delete()
    {
        throw new \Exception('Cannot directly delete Syncable resources ' . __CLASS__);
    }

    public function syncDelete()
    {
        parent::delete();
    }

    public function getStatus()
    {
        if (!$this->syncs()->count()) {
            return 'complete';
        }
        if ($this->getSyncFailed()) {
            return 'failed';
        }
        if ($this->syncs()->latest()->first()->completed) {
            return 'complete';
        }
        return 'in-progress';
    }

    public function syncs()
    {
        return $this->hasMany(Sync::class, 'resource_id', 'id');
    }

    public function getSyncFailed()
    {
        if (!$this->syncs()->count()) {
            return false;
        }
        return $this->syncs()->latest()->first()->failure_reason !== null;
    }

    public function createSync()
    {
        Log::info(get_class($this) . ' : Creating new sync - Started', ['resource_id' => $this->id]);

        if ($this->getStatus() !== 'complete') {
            Log::info(get_class($this) . ' : Tried to create a new sync on ' . __CLASS__ . ' with outstanding sync', [
                'resource_id' => $this->id
            ]);
            return false;
        }

        $sync = app()->make(Sync::class);
        $sync->resource_id = $this->id;
        $sync->completed = false;
        $sync->save();
        Log::info(get_class($this) . ' : Creating new sync - Finished', ['resource_id' => $this->id]);

        return $sync;
    }

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

    public function setSyncFailureReason($value)
    {
        if (!$this->syncs()->count()) {
            return;
        }
        $sync = $this->syncs()->latest()->first();
        $sync->failure_reason = $value;
        $sync->save();
    }

    public function getSyncFailureReason()
    {
        if (!$this->syncs()->count()) {
            return null;
        }
        return $this->syncs()->latest()->first()->failure_reason;
    }

    /**
     * TODO :- Come up with a nicer way to do this as this is disgusting!
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSyncError()
    {
        return \Illuminate\Http\JsonResponse::create(
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
