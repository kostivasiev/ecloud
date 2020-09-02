<?php

namespace App\Listeners\V2;

use App\Models\V2\Router;
use App\Services\NsxService;
use App\Events\V2\FirewallRuleCreated;
use App\Models\V2\FirewallRule;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        $nsxClient = $router->vpc->region->availabilityZones()->first()->nsxClient();

        try {
            $response = $nsxClient->get('policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra/rules/' . $router->id . '-tier1-default_blacklist_rule');
            $original = json_decode($response->getBody()->getContents(), true);
            $original['action'] = 'REJECT';
            $original['display_name'] = $firewallRule->id;
            $original = array_filter($original, function ($key) {
                return strpos($key, '_') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            $nsxClient->patch('policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra/rules/' . $router->id . '-tier1-default_blacklist_rule', [
                'json' => $original
            ]);
        } catch (RequestException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }

        $firewallRule->deployed = true;
        $firewallRule->save();
    }
}
