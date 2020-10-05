<?php

namespace App\Listeners\V2;

use App\Events\V2\FirewallRuleCreated;
use App\Models\V2\FirewallRule;
use App\Models\V2\Router;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class FirewallRuleDeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param FirewallRuleCreated $event
     * @return void
     * @throws \Exception
     */
    public function handle(FirewallRuleCreated $event)
    {
        /** @var FirewallRule $firewallRule */
        $firewallRule = $event->firewallRule;
        $router = Router::findOrFail($firewallRule->router->id);
        $nsxService = $router->vpc->region->availabilityZones()->first()->nsxService();

        try {
            // TODO
        } catch (RequestException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }

        $firewallRule->deployed = true;
        $firewallRule->save();
    }
}
