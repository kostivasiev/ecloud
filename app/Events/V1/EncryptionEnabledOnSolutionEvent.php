<?php

namespace App\Events\V1;

use App\Events\Event;
use App\Models\V1\Solution;

/**
 * Class ApplianceDeletedEvent
 *
 * An event to be triggered on deleting Appliance records.
 *
 * @package App\Events\V1
 */
class EncryptionEnabledOnSolutionEvent extends Event
{
    public $solution;

    /**
     * EncryptionEnabledOnSolutionEvent constructor.
     * @param Solution $solution
     */
    public function __construct(Solution $solution)
    {
        $this->solution = $solution;
    }
}
