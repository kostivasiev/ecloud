<?php

namespace App\Events\V2\Instance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Updated
{
    use SerializesModels;

    public $model;
    public $original;

    /**
     * @param Model $model
     * @return void
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->original = $model->getOriginal();
    }
}
