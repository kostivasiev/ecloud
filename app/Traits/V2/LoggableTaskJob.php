<?php
namespace App\Traits\V2;

trait LoggableTaskJob
{
    use LoggableModelJob;

    public function resolveModelId()
    {
        return $this->task->resource ? $this->task->resource->id : null;
    }

}
