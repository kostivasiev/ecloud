<?php

namespace App\Listeners\V1;

use App\Events\V1\DatastoreCreatedEvent;
use Illuminate\Http\Request;
use Log;

/**
 * Class DatastoreCreatedListener
 *
 * Listener for DatastoreCreatedEvent.
 *
 * - Logs to lumen log
 *
 * @package App\Listeners\V1
 */
class DatastoreCreatedListener
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

    public function handle(DatastoreCreatedEvent $event)
    {
        // Log the appliance deletion
        Log::info(
            'New Datastore Creation was scheduled',
            [
                'id' => $event->datastore->getKey(),
                'reseller_id' => $this->request->user()->resellerId(),
                'sizeGB' => $event->datastore->reseller_lun_size_gb
            ]
        );
    }
}
