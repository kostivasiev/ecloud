<?php

namespace App\Events\V2\Dhcp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Deleted
{
    use SerializesModels;

    public $id;

    public function __construct(Model $model)
    {
        $this->id = $model->id;
    }
}