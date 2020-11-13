<?php

namespace App\Events\V2\FirewallPolicy;

use App\Events\Event;
use Illuminate\Database\Eloquent\Model;

class Deleted extends Event
{
    public $model;
    public $firewallPolicyId;

    /**
     * @param Model $model
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->firewallPolicyId = $model->getKey();
    }
}
