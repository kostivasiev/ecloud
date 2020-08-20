<?php

namespace App\Observers\V2;

use App\Models\V2\Router;
use App\Models\V2\Vpn;

class VpnObserver
{
    /**
     * @param Vpn $vpn
     * @return void
     */
    public function creating(Vpn $vpn)
    {
        Router::forUser(app('request')->user)->findOrFail($vpn->router_id);
    }

    /**
     * @param Vpn $vpn
     * @return void
     */
    public function updating(Vpn $vpn)
    {
        if (!empty($vpn->router_id)) {
            Router::forUser(app('request')->user)->findOrFail($vpn->router_id);
        }
    }
}
