<?php

namespace App\Events\V2\Volume;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Synced
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
