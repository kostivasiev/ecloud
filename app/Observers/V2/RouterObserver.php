<?php

namespace App\Observers\V2;

use App\Models\V2\Router;
use App\Models\V2\Vpc;

class RouterObserver
{
    /**
     * @param Router $router
     * @return void
     */
    public function creating(Router $router)
    {
        Vpc::forUser(app('request')->user)->findOrFail($router->vpc_id);
    }

    /**
     * @param Router $router
     * @return void
     */
    public function updating(Router $router)
    {
        if (!empty($router->vpc_id)) {
            Vpc::forUser(app('request')->user)->findOrFail($router->vpc_id);
        }
    }
}
