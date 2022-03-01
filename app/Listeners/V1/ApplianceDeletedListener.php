<?php

namespace App\Listeners\V1;

use App\Events\V1\ApplianceDeletedEvent;
use Illuminate\Http\Request;
use Log;
use UKFast\Api\Exceptions\DatabaseException;

/**
 * Class ApplianceDeletedListener
 *
 * Listener for ApplianceDeletedEvent. Handles actions to perform on deleting an appliance record.
 *
 * - Logs to lumen log
 * - Cascades the delete action to an associated appliance version records.
 *
 * @package App\Listeners\V1
 */
class ApplianceDeletedListener
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

    public function handle(ApplianceDeletedEvent $event)
    {
        // Log the appliance deletion
        Log::info(
            'Appliance Deleted',
            [
                'id' => $event->appliance->id,
                'reseller_id' => $this->request->user()->resellerId()
            ]
        );

        // Cascade soft delete to appliance versions (which will cascade to appliance parameters)
        foreach ($event->appliance->versions as $applianceVersion) {
            try {
                $applianceVersion->delete();
            } catch (\Exception $exception) {
                throw new DatabaseException('Failed to delete the appliance versions');
            }
        }
    }
}
