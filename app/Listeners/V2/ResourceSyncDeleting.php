<?php

namespace App\Listeners\V2;

use App\Exceptions\TaskException;
use App\Models\V2\Sync;
use Illuminate\Support\Facades\Log;

class ResourceSyncDeleting
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['resource_id' => $event->model->id]);

        if (($event->model->sync->status === Sync::STATUS_COMPLETE) && $event->model->sync->type == Sync::TYPE_DELETE) {
            Log::info(get_class($this) . ' : Delete sync complete, not blocking deletion', ['resource_id' => $event->model->id]);
            return true;
        }

        if (!$event->model->canCreateTask()) {
            throw new TaskException();
        }

        $event->model->createTask('sync_delete', $event->model->getDeleteSyncJob());

        Log::info(get_class($this) . ' : Finished', ['resource_id' => $event->model->id]);
        return false;
    }
}
