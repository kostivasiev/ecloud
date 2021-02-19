<?php

namespace App\Listeners\V1;

use App\Events\V1\ApplianceVersionDeletedEvent;
use Illuminate\Http\Request;
use Log;
use UKFast\Api\Exceptions\DatabaseException;

/**
 * Class ApplianceVersionDeletedListener
 *
 * Listener for ApplianceVersionDeletedEvent. Handles actions to perform on deleting an appliance version record.
 *
 * - Logs the deletion in the lumen log
 * - Cascades the delete to any appliance version parameter.
 *
 * @package App\Listeners\V1
 */
class ApplianceVersionDeletedListener
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

    /**
     * Handle the deletion of an appliance version
     * @param ApplianceVersionDeletedEvent $event
     * @throws DatabaseException
     */
    public function handle(ApplianceVersionDeletedEvent $event)
    {
        // Log the appliance version deletion
        Log::info(
            'Appliance Version Deleted',
            [
                'id' => $event->applianceVersion->id,
                'appliance_id' => $event->applianceVersion->appliance->id,
                'reseller_id' => $this->request->user()->resellerId()
            ]
        );

        /**
         * Cascade the (soft) delete to the version parameters
         *
         * If we want to just log the delete of the version and not all the parameters we could do this:
         * $event->applianceVersion->parameters()->delete();
         */
        foreach ($event->applianceVersion->parameters as $parameter) {
            try {
                $parameter->delete();
            } catch (\Exception $exception) {
                throw new DatabaseException('Unable to delete Appliance version parameters');
            }
        }
    }
}
