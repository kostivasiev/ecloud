<?php

namespace App\Events\V1;

use App\Events\Event;
use App\Models\V1\Appliance;

/**
 * Class ApplianceLaunchedEvent
 *
 * An event to be triggered on launching appliances.
 *
 * @package App\Events\V1
 */
class ApplianceLaunchedEvent extends Event
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
