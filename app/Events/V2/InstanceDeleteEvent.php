<?php

namespace App\Events\V2;

use App\Models\V2\Instance;
use Illuminate\Queue\SerializesModels;

class InstanceDeleteEvent
{
    use SerializesModels;

    public Instance $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }
}
