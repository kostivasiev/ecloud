<?php

namespace App\Observers\V2;

use App\Models\V2\Instance;
use App\Models\V2\Network;

class InstanceObserver
{
    /**
     * @param Instance $instance
     * @return void
     */
    public function creating(Instance $instance)
    {
        Network::forUser(app('request')->user)->findOrFail($instance->network_id);
    }

    /**
     * @param Instance $instance
     * @return void
     */
    public function updating(Instance $instance)
    {
        if (!empty($instance->network_id)) {
            Network::forUser(app('request')->user)->findOrFail($instance->network_id);
        }
    }
}
