<?php

namespace App\Events\V2\AclPolicy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Updated
{
    use SerializesModels;

    public $model;

    /**
     * @param Model $model
     * @return void
     */
    public function __construct($model)
    {
        $this->model = $model;
    }
}
