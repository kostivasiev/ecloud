<?php
namespace App\Listeners\V2\Router\FirewallPolicies;

use App\Events\V2\Router\Deleted;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class Delete
{
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);
        $router = Router::withTrashed()->findOrFail($event->model->getKey());
        $router->firewallPolicies()->each(function ($policy) {
            $policy->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
