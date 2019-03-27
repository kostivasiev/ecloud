<?php

namespace App\Solution;

class Status
{
    /**
     * Solution is in a Completed state
     */
    const COMPLETED = 'Completed';

    /**
     * Solution has a custom configuration / state
     */
    const CUSTOM = 'Custom';

    /**
     * The reseller's Solution has been cancelled
     */
    const CANCELLED = 'Cancelled';

    /**
     * Unkown state
     */
    const UNKNOWN = 'Unknown';
}
