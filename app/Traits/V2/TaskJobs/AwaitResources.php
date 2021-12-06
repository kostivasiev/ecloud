<?php
namespace App\Traits\V2\TaskJobs;

use App\Support\Resource;
use App\Support\Sync;
use App\Traits\V2\Syncable;

trait AwaitResources
{
    public $tries = 60;

    public $backoff = 5;

    protected function awaitSyncableResources(Array $resources = [])
    {
        foreach ($resources as $id) {
            $resource = Resource::classFromId($id)::findOrFail($id);

            if (!in_array(Syncable::class, class_uses($resource))) {
                $this->error('Failed to check sync state, resource is not a Syncable resource, abort', ['target_resource' => $resource->id]);
                $this->fail(new \Exception("Resource '" . $resource->id . "' is not a syncable resource"));
                return;
            }

            if ($resource->sync->status == Sync::STATUS_FAILED) {
                $this->error('Resource in failed sync state, abort', ['target_resource' => $resource->id]);
                $this->fail(new \Exception("Resource '" . $resource->id . "' in failed sync state"));
                return;
            }

            if ($resource->sync->status != Sync::STATUS_COMPLETE) {
                $this->warning('Resource not in sync, retrying in ' . $this->backoff . ' seconds', ['target_resource' => $resource->id]);
                $this->release($this->backoff);
                return;
            }
        }
    }
}
