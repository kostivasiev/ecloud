<?php

namespace App\Events\V2\Router;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Deleted
{
    use SerializesModels;

    public $model;
    public $routerId;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->routerId = $model->getKey();
    }
}
