<?php

namespace App\Events\V2\Volume;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Saved
{
    use SerializesModels;

    public $model;
    public $originalCapacity;
    public $originalIops;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->originalCapacity = $model->getOriginal('capacity');
        $this->originalIops = $model->getOriginal('iops');
    }
}
