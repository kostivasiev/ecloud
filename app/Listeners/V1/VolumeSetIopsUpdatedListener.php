<?php

namespace App\Listeners\V1;

use App\Events\V1\VolumeSetIopsUpdatedEvent;
use Illuminate\Http\Request;
use Log;

/**
 * Class VolumeSetIopsUpdatedListener
 *
 * Listener for VolumeSetIopsUpdatedEvent.
 *
 * - Logs to lumen log
 *
 *
 * @package App\Listeners\V1
 */
class VolumeSetIopsUpdatedListener
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

    public function handle(VolumeSetIopsUpdatedEvent $event)
    {
        Log::info(
            'IOPS limit updated for Volume Set',
            [
                'volume_set_id' => $event->volumeSet->getKey(),
                'reseller_id' => $this->request->user()->resellerId(),
                'new_iops_limit' => $event->volumeSet->max_iops
            ]
        );
    }
}
