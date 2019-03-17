<?php

namespace App\Listeners\V1;

use App\Events\V1\AppliancePodAvailabilityDeletedEvent;
use Illuminate\Http\Request;
use Log;

class AppliancePodAvailabilityDeletedListener
{
    public $request;

    /**
     * Create the event listener.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle(AppliancePodAvailabilityDeletedEvent $event)
    {
        // Log the appliance deletion
        Log::info(
            'Appliance removed from Pod',
            [
                'appliance_id' => $event->appliancePodAvailability->appliance_id,
                'pod_id' => $event->appliancePodAvailability->ucs_datacentre_id
            ]
        );
    }
}
