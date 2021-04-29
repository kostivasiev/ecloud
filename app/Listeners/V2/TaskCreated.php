<?php

namespace App\Listeners\V2;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\FloatingIp;
use App\Models\V2\Host;
use App\Models\V2\Dhcp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Router;
use App\Support\Sync;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class TaskCreated
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        // TODO: Remove following once all syncable resources are using update/delete functionality
        if (!in_array(get_class($event->model->resource), [
            Volume::class,
            Instance::class,
            Nic::class,
            FloatingIp::class,
            FirewallPolicy::class,
            Vpc::class,
            Dhcp::class,
            Router::class,
            Network::class,
            Host::class,
            Nat::class,
        ])) {
            return true;
        }

        Log::debug(get_class($this) . " : Dispatching job", ["job" => $event->model->job]);
        dispatch(new $event->model->job($event->model));

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
