<?php

namespace App\Listeners\V2\FirewallRule;

use App\Events\V2\FirewallRule\Created;
use App\Models\V2\FirewallRule;
use App\Models\V2\Router;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Deploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Created $event
     * @return void
     * @throws Exception
     */
    public function handle(Created $event)
    {
        /** @var FirewallRule $firewallRule */
        $firewallRule = $event->model;
        $router = Router::findOrFail($firewallRule->router->id);
        $nsxService = $router->vpc->region->availabilityZones()->first()->nsxService();

        try {
            // TODO
            Log::notice('FirewallRule deployment not implemented yet');
        } catch (RequestException $exception) {
            throw new Exception($exception->getResponse()->getBody()->getContents());
        }

        $firewallRule->deployed = true;
        $firewallRule->save();
    }
}
