<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\Job;
use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RemoveDHCPLease extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Nic $nic)
    {
        $this->model = $nic;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);
        $nic = $this->model;

        $nsxService = $this->model->instance->availabilityZone->nsxService();

        /**
         * Delete dhcp lease for the ip to the nic's mac address on NSX
         * See: https://network-man0.ecloud-service.ukfast.co.uk/policy/api_includes/method_DeleteSegmentDhcpStaticBinding.html
         */
        $nsxService->delete(
            '/policy/api/v1/infra/tier-1s/' . $nic->network->router->id .
            '/segments/' . $nic->network->id .
            '/dhcp-static-binding-configs/' . $nic->id
        );
        Log::info('DHCP static binding deleted for ' . $nic->id . ' (' . $nic->mac_address . ') with IP ' . $nic->ip_address);

        $nic->refresh();
        $ipAddress = $nic->ipAddresses()->withType(IpAddress::TYPE_NORMAL)->first();

        if (!empty($ipAddress)) {
            $nic->ipAddresses()->detach($ipAddress->id);
            $ipAddress->delete();
            Log::info('IP address ' . $ipAddress->id . ' (' . $ipAddress->ip_address . ') deleted from NIC ' . $nic->id . ' on network ' . $nic->network->id);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $nic->id]);
    }
}
