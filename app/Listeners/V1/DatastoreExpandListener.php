<?php

namespace App\Listeners\V1;

use App\Events\V1\DatastoreExpandEvent;
use Illuminate\Http\Request;
use Log;

/**
 * Class DatastoreExpandListener
 *
 * Listener for DatastoreExpandEvent.
 *
 * - Logs to lumen log
 *
 * @package App\Listeners\V1
 */
class DatastoreExpandListener
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

    public function handle(DatastoreExpandEvent $event)
    {
        // Log the appliance deletion
        Log::info(
            'Datastore expand was scheduled',
            [
                'id' => $event->datastore->getKey(),
                'reseller_id' => $this->request->user()->resellerId(),
                'new_size_gb' => $event->newSizeGb
            ]
        );
    }
}
