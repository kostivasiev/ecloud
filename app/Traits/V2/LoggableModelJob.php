<?php
namespace App\Traits\V2;

trait LoggableModelJob
{
    use LoggableJob;

    public function resolveModelId()
    {
        return $this->model ? $this->model->id : null;
    }

    public function getLoggingData()
    {
        return [
            'id' => $this->resolveModelId(),
        ];
    }
}
