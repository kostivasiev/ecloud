<?php

namespace App\Events\V1;

use App\Events\Event;
use App\Models\V1\AppliancePodAvailability;

/**
 * Class AppliancePodAvailabilityDeletedEvent
 *
 * An event to be triggered on deleting Appliance from a pod.
 *
 * @package App\Events\V1
 */
class AppliancePodAvailabilityDeletedEvent extends Event
{
    public $appliancePodAvailability;

    /**
     * $appliancePodAvailability constructor.
     * @param AppliancePodAvailability $appliancePodAvailability
     */
    public function __construct(AppliancePodAvailability $appliancePodAvailability)
    {
        $this->appliancePodAvailability = $appliancePodAvailability;
    }
}
