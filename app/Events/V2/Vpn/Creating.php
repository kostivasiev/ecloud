<?php

namespace App\Events\V2\Vpn;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Creating
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
