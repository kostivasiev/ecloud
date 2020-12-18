<?php

namespace App\Events\V2\Nat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Saving
{
    use SerializesModels;

    public Model $model;

    /**
     * @param Model $model
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}
