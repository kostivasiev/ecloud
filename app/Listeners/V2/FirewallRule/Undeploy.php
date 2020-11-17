<?php

namespace App\Listeners\V2\FirewallRule;

use App\Events\V2\FirewallRule\Deleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Undeploy implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @see https://185.197.63.88/policy/api_includes/method_DeleteGatewayRule.html
     *
     * TODO: For some reason patching the gateway policy does not remove the rule from NSX as expected,
     * so we're going to have to do it explicitly. A ticket has been opened with VMWare regarding this, see
     * https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/533#
     *
     * @param Deleted $event
     * @return void
     */
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $firewallRule = $event->model;

        $firewallRule->firewallPolicy->router->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/domains/default/gateway-policies/' . $firewallRule->firewallPolicy->getKey() . '/rules/' . $firewallRule->getKey()
        );

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}