<?php
namespace App\Traits\V2;

trait LoggableModelJob
{
    use LoggableJob;

    public function resolveModelId()
    {
        return $this->model->id;
    }

    public function getLoggingData()
    {
        return [
            'id' => $this->resolveModelId(),
        ];
    }
}
