<?php

namespace App\Console\Commands\Nsx;

use App\Models\V2\Router;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;

class TestFirewallRuleDeploy extends Command
{
    protected $signature = 'nsx:test-update-default-router-firewall-rule {routerId}';

    protected $description = 'Performs firewall rule update against the configured NSX router';

    public function handle()
    {
        $router = Router::findOrFail($this->argument('routerId'));
        $availabilityZone = $router->vpc->region->availabilityZones()->first();
        $nsxClient = $availabilityZone->nsxClient();

        try {
            //$response = $nsxClient->get('policy/api/v1/infra/tier-1s/' . $router->id . '/gateway-firewall');
            $response = $nsxClient->get('policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra/rules/' . $router->id . '-tier1-default_blacklist_rule');
            $original = json_decode($response->getBody()->getContents(), true);
            $original['action'] = 'REJECT';
            //$original['ip_protocol'] = 'IPV4_IPV6';
            $original = array_filter($original, function ($key) {
                return strpos($key, '_') !== 0;
            }, ARRAY_FILTER_USE_KEY);


            $keysToDelete = [
                'disabled',
                'destination_groups',
            ];
            foreach ($keysToDelete as $keyToDelete) {
                $original[$keyToDelete] = null;
                unset($original[$keyToDelete]);
            }


            $response = $nsxClient->patch('policy/api/v1/infra/domains/default/gateway-policies/Policy_Default_Infra/rules/' . $router->id . '-tier1-default_blacklist_rule', [
                'json' => $original
            ]);
        } catch (RequestException $exception) {
            dd($exception->getResponse()->getBody()->getContents());
        }

        dd($response->getBody()->getContents());
    }
}
