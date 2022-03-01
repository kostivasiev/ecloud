<?php

namespace App\Events\V1;

use App\Events\Event;
use App\Models\V1\ApplianceParameter;

/**
 * Class ApplianceParameterDeletedEvent
 *
 * An event to be triggered on deleting Appliance script parameters
 *
 * @package App\Events\V1
 */
class ApplianceParameterDeletedEvent extends Event
{
    public $applianceParameter;

    /**
     * ApplianceParameterDeletedEvent constructor.
     * @param ApplianceParameter $applianceParameter
     */
    public function __construct(ApplianceParameter $applianceParameter)
    {
        $this->applianceParameter = $applianceParameter;
    }
}
