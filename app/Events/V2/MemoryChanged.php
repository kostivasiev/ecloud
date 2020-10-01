<?php

namespace App\Events\V2;

use App\Models\V2\Instance;
use Illuminate\Queue\SerializesModels;

class MemoryChanged
{
    use SerializesModels;

    /**
     * @var $instance
     */
    public $instance;

    /**
     * @param $instance
     * @return void
     */
    public function __construct($instance)
    {
        $this->instance = $instance;
    }
}
