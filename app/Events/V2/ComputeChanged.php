<?php

namespace App\Events\V2;

use App\Models\V2\Instance;
use Illuminate\Queue\SerializesModels;

class ComputeChanged
{
    use SerializesModels;

    public $instance;
    public $rebootRequired;

    /**
     * @param $instance
     * @param  bool  $rebootRequired
     */
    public function __construct($instance, bool $rebootRequired = false)
    {
        $this->instance = $instance;
        $this->rebootRequired = $rebootRequired;
    }
}
