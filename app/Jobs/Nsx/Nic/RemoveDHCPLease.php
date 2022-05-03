<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\TaskJob;
use App\Models\V2\IpAddress;

class RemoveDHCPLease extends TaskJob
{
    public function handle()
    {
        $nic = $this->task->resource;

        $nsxService = $nic->instance->availabilityZone->nsxService();

        /**
         * Delete dhcp lease for the ip to the nic's mac address on NSX
         * See: https://network-man0.ecloud-service.ukfast.co.uk/policy/api_includes/method_DeleteSegmentDhcpStaticBinding.html
         */
        $nsxService->delete(
            '/policy/api/v1/infra/tier-1s/' . $nic->network->router->id .
            '/segments/' . $nic->network->id .
            '/dhcp-static-binding-configs/' . $nic->id
        );
        $this->info('DHCP static binding deleted for ' . $nic->id . ' (' . $nic->mac_address . ') with IP ' . $nic->ip_address);

        $nic->refresh();
        $ipAddress = $nic->ipAddresses()->withType(IpAddress::TYPE_DHCP)->first();

        if (!empty($ipAddress)) {
            $nic->ipAddresses()->detach($ipAddress->id);
            $ipAddress->delete();
            $this->info('IP address ' . $ipAddress->id . ' (' . $ipAddress->ip_address . ') deleted from NIC ' . $nic->id . ' on network ' . $nic->network->id);
        }
    }
}
