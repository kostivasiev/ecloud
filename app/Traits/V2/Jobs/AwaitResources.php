<?php
namespace App\Traits\V2\Jobs;

use App\Support\Resource;
use App\Support\Sync;
use App\Traits\V2\Syncable;
use Illuminate\Support\Facades\Log;

trait AwaitResources
{
    public $tries = 60;

    public $backoff = 30;

    protected function awaitSyncableResources(Array $resources = [])
    {
        foreach ($resources as $id) {
            $resource = Resource::classFromId($id)::findOrFail($id);

            if (!in_array(Syncable::class, class_uses($resource))) {
                Log::error(get_class($this) . ': Failed to check sync state, resource is not a Syncable resource, abort', ['id' => $this->model->id, 'resource' => $resource->id]);
                $this->fail(new \Exception("Resource '" . $resource->id . "' is not a syncable resource"));
                return;
            }

            if ($resource->sync->status == Sync::STATUS_FAILED) {
                Log::error(get_class($this) . ': Resource in failed sync state, abort', ['id' => $this->model->id, 'resource' => $resource->id]);
                $this->fail(new \Exception("Resource '" . $resource->id . "' in failed sync state"));
                return;
            }

            if ($resource->sync->status != Sync::STATUS_COMPLETE) {
                Log::warning(get_class($this) . ': Resource not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'resource' => $resource->id]);
                $this->release($this->backoff);
                return;
            }
        }
    }
}
