<?php

namespace App\Events\V2\FirewallPolicy;

use App\Events\Event;
use Illuminate\Database\Eloquent\Model;

class Deleting extends Event
{
    public $model;

    /**
     * @param Model $model
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
}
