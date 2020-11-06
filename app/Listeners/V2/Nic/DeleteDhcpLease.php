<?php

namespace App\Listeners\V2\Nic;

use App\Events\V2\Nic\Deleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class DeleteDhcpLease implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @see https://185.197.63.88/policy/api_includes/method_DeleteSegmentDhcpStaticBinding.html
     * @param Deleted $event
     * @return void
     */
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $nic = $event->model;

        $logMessage = 'DeleteDhcpLease for NIC ' . $nic->getKey() . ': ';
        Log::info($logMessage . 'Started');

        $network = $nic->network;
        $router = $nic->network->router;
        $nsxService = $nic->instance()->withTrashed()->first()->availabilityZone->nsxService();

        //Delete dhcp lease for the ip to the nic's mac address on NSX
        $nsxService->delete(
            '/policy/api/v1/infra/tier-1s/' . $router->getKey() . '/segments/' . $network->getKey()
            . '/dhcp-static-binding-configs/' . $nic->getKey()
        );

        Log::info('DHCP static binding deleted for ' . $nic->getKey() . ' (' . $nic->mac_address . ') with IP ' . $nic->ip_address);

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
