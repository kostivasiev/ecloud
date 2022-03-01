<?php

namespace App\Events\V1;

use App\Events\Event;
use App\Models\V1\ApplianceVersion;

/**
 * Class ApplianceVersionDeletedEvent
 *
 * An event to be triggered on deleting an appliance version record.
 *
 * @package App\Events\V1
 */
class ApplianceVersionDeletedEvent extends Event
{
    public $applianceVersion;

    /**
     * ApplianceParameterDeletedEvent constructor.
     * @param ApplianceVersion $applianceVersion
     */
    public function __construct(ApplianceVersion $applianceVersion)
    {
        $this->applianceVersion = $applianceVersion;
    }
}
