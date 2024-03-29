<?php
namespace App\Traits\V2\TaskJobs;

use App\Support\Resource;
use App\Support\Sync;
use App\Traits\V2\Syncable;
use Illuminate\Database\Eloquent\Model;

trait AwaitResources
{
    public $tries = 60;

    public $backoff = 5;

    public function createSyncableResource($resourceType, $attributes = [], $callback = null): ?Model
    {
        if (!in_array(Syncable::class, class_uses($resourceType))) {
            $this->error($this::class . ': ResourceType '. $resourceType . ' is not a Syncable resource, abort');
            $this->fail(new \Exception('ResourceType '. $resourceType . ' is not a Syncable resource'));
            return null;
        }

        $resource = $resourceType::firstOrNew($attributes);

        if (!empty($callback)) {
            $callback($resource);
        }

        if (!$resource->exists) {
            $resource->syncSave();
        }

        if ($resource->sync->status == Sync::STATUS_FAILED) {
            $this->error('Resource ' . $resource->id . ' in failed sync state, abort');
            $this->fail(new \Exception("Resource '" . $resource->id . "' in failed sync state"));
            return null;
        }

        if ($resource->sync->status != Sync::STATUS_COMPLETE) {
            $this->warning('Resource ' . $resource->id . ' not in sync, retrying in ' . $this->backoff . ' seconds');
            $this->release($this->backoff);
            return null;
        }

        return $resource;
    }

    public function deleteSyncableResource($id): void
    {
        $resourceType = Resource::classFromId($id);

        if (!in_array(Syncable::class, class_uses($resourceType))) {
            $this->error('ResourceType '. $resourceType . ' is not a Syncable resource, abort');
            $this->fail(new \Exception('ResourceType '. $resourceType . ' is not a Syncable resource'));
            return;
        }

        $resource = $resourceType::find($id);

        if (!$resource) {
            $this->info('Resource ' . $id . ' has been deleted');
            return;
        }

        if ($resource->sync->status == Sync::STATUS_COMPLETE && $resource->sync->type != Sync::TYPE_DELETE) {
            $this->info('Deleting Resource ' . $id);
            $resource->syncDelete();
        }

        if ($resource->sync->status != Sync::STATUS_COMPLETE && $resource->sync->type == Sync::TYPE_DELETE) {
            $this->info('Waiting for ' . $resource->id . ' to be deleted, retrying in ' . $this->backoff . ' seconds.');
            $this->release($this->backoff);
        }
    }

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
