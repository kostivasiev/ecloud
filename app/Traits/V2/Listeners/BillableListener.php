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

        if (!$event->model->completed) {
            return false;
        }

        if (get_class($event->model->resource) != static::RESOURCE) {
            return false;
        }

        if ($event->model->resource instanceof Manageable && $event->model->resource->isManaged()) {
            return false;
        }

        return true;
    }
}
