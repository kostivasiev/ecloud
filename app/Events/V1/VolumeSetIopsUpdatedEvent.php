<?php

namespace App\Events\V1;

use App\Events\Event;
use App\Models\V1\VolumeSet;

/**
 * Class VolumeSetIopsUpdatedEvent
 * @package App\Events\V1
 */
class VolumeSetIopsUpdatedEvent extends Event
{
    public $volumeSet;

    /**
     * @param VolumeSet $volumeSet
     */
    public function __construct(VolumeSet $volumeSet)
    {
        $this->volumeSet = $volumeSet;
    }
}
