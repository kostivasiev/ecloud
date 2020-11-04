<?php

namespace App\Events\V2\Vpc;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class Deleted
{
    use SerializesModels;

    public $id;
    public $dhcpId;

    public function __construct(Model $model)
    {
        $this->id = $model->id;
        $this->dhcpId = $model->dhcp->id;
    }
}
