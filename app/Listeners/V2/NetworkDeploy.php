<?php

namespace App\Listeners\V2;

use App\Events\V2\NetworkCreated;
use App\Events\V2\RouterCreated;

class NetworkDeploy
{
    /**
     * Handle the event.
     *
     * @param  RouterCreated  $event
     * @return void
     */
    public function handle(RouterCreated $event)
    {
        $router = $event->router;
        if ($router->network()->count() > 0) {
            /** @var \App\Models\V2\Network $network */
            $network = $router->network()->first();
            event(new NetworkCreated($network));
        }
    }
}
