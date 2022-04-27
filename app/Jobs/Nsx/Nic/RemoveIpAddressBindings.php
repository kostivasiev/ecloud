<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\TaskJob;

class RemoveIpAddressBindings extends TaskJob
{
    /**
     * Patch a Tier-1 segment port with an IP address binding
     * @see: https://vdc-download.vmware.com/vmwb-repository/dcr-public/787988e9-6348-4b2a-8617-e6d672c690ee/a187360c-77d5-4c0c-92a8-8e07aa161a27/api_includes/method_DeleteInfraSegmentPort.html
     * @return bool|void
     * @throws \Exception
     */
    public function handle()
    {
        $nic = $this->task->resource;
        $network = $nic->network;
        $router = $nic->network->router;
        $nsxService = $router->availabilityZone->nsxService();

        $nic->refresh();

        $nsxService->delete(
            '/policy/api/v1/infra/tier-1s/' . $router->id .
            '/segments/' . $network->id .
            '/ports/' . $nic->id
        );

        $nic->ipAddresses()->sync([]);

        $this->info('Address bindings removed for ' . $nic->id . ' (' . $nic->mac_address . ')');
    }
}
