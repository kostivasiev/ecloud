<?php

namespace App\Listeners\V2\FirewallPolicy;

use App\Events\V2\FirewallPolicy\Deleted;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Undeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @see https://vdc-download.vmware.com/vmwb-repository/dcr-public/9e1c6bcc-85db-46b6-bc38-d6d2431e7c17/30af91b5-3a91-4d5d-8ed5-a7d806764a16/api_includes/method_DeleteGatewayPolicy.html
     * @param Deleted $event
     * @return void
     */
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $firewallPolicy = $event->model;
        $message = 'Undeploy Firewall Policy ' . $firewallPolicy->getKey() .': ';
        Log::info($message . 'Started');

        $firewallPolicy->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/domains/default/gateway-policies/' . $firewallPolicy->getKey()
        );

        $firewallPolicy->firewallRules->each(function ($firewallRule) {
            $firewallRule->delete();
        });

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
