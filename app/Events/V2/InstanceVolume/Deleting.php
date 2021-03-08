<?php

namespace App\Events\V2\InstanceVolume;

use Illuminate\Queue\SerializesModels;

class Deleting
{
    use SerializesModels;

    public $model;

    public function __construct($model)
    {
        $this->model = $model;
    }
}
