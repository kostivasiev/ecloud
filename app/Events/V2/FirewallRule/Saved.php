<?php

namespace App\Events\V2\FirewallRule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Saved
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
