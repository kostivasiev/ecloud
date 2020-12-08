<?php

namespace App\Events\V2\Dhcp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Saving
{
    use SerializesModels;

    public Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}
