<?php

namespace App\Listeners\V1;

use App\Events\V1\ApplianceLaunchedEvent;
use Illuminate\Http\Request;
use Log;

/**
 * Class ApplianceLaunchedListener
 *
 * Listener for ApplianceLaunchedEvent.
 *
 * - Logs to lumen log.
 *
 * @package App\Listeners\V1
 */
class ApplianceLaunchedListener
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

    public function handle(ApplianceLaunchedEvent $event)
    {
        // Log the appliance deletion
        Log::info(
            'Appliance Launched',
            [
                'id' => $event->appliance->id,
                'name' => $event->appliance->name,
                'version' => $event->appliance->version,
                'reseller_id' => $this->request->user()->resellerId()
            ]
        );
    }
}
