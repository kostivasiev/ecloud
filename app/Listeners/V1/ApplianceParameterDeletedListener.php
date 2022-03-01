<?php

namespace App\Listeners\V1;

use App\Events\V1\ApplianceParameterDeletedEvent;
use Illuminate\Http\Request;
use Log;

/**
 * Class ApplianceParameterDeletedListener
 *
 * Listener for ApplianceParameterDeletedEvent. Handles actions to perform on deleting an appliance script parameter.
 *
 * - Logs the deletion to the lumen log.
 *
 * @package App\Listeners\V1
 */
class ApplianceParameterDeletedListener
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

    public function handle(ApplianceParameterDeletedEvent $event)
    {
        Log::info(
            'Appliance Script Parameter Deleted',
            [
                'id' => $event->applianceParameter->id,
                'appliance_version_id' => $event->applianceParameter->appliance_version_id,
                'appliance_id' => $event->applianceParameter->applianceVersion->appliance->id,
                'reseller_id' => $this->request->user()->resellerId()
            ]
        );
    }
}
