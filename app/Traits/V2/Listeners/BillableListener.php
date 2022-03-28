<?php

namespace App\Traits\V2\Listeners;

use App\Events\V2\Task\Updated;
use App\Models\V2\Manageable;
use App\Support\Sync;

trait BillableListener
{
    protected function validateBillableResourceEvent(Updated $event): bool
    {
        if ($event->model->name != Sync::TASK_NAME_UPDATE) {
            return false;
        }

        return $this->validateEventStatusAndResource($event);
    }

    protected function validateNotDeletedResourceEvent(Updated $event): bool
    {
        if ($event->model->name == Sync::TASK_NAME_DELETE) {
            return false;
        }

        return $this->validateEventStatusAndResource($event);
    }

    protected function validateEventStatusAndResource(Updated $event): bool
    {
        if (!$event->model->completed) {
            return false;
        }

        if (is_array(static::RESOURCE)) {
            if (!in_array($event->model->resource::class, static::RESOURCE)) {
                return false;
            }
        } else if ($event->model->resource::class != static::RESOURCE) {
            return false;
        }

        if ($event->model->resource instanceof Manageable && $event->model->resource->isManaged()) {
            return false;
        }

        return true;
    }
}
