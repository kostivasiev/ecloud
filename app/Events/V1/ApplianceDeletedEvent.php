<?php

namespace App\Events\V1;

use App\Events\Event;
use App\Models\V1\Appliance;

/**
 * Class ApplianceDeletedEvent
 *
 * An event to be triggered on deleting Appliance records.
 *
 * @package App\Events\V1
 */
class ApplianceDeletedEvent extends Event
{
    public $appliance;

    /**
     * ApplianceDeletedEvent constructor.
     * @param Appliance $appliance
     */
    public function __construct(Appliance $appliance)
    {
        $this->appliance = $appliance;
    }
}
