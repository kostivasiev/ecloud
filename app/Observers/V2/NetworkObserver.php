<?php

namespace App\Observers\V2;

use App\Models\V2\Router;
use App\Models\V2\Network;

class NetworkObserver
{
    /**
     * @param Network $network
     * @return void
     */
    public function creating(Network $network)
    {
        Router::forUser(app('request')->user)->findOrFail($network->router_id);
    }

    /**
     * @param Network $network
     * @return void
     */
    public function updating(Network $network)
    {
        if (!empty($network->router_id)) {
            Router::forUser(app('request')->user)->findOrFail($network->router_id);
        }
    }
}
