<?php

namespace App\Events\V2\Task;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Created
{
    use SerializesModels;

    public $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}
