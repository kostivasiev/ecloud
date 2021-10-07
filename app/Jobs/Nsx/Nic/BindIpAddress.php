<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\Job;
use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class BindIpAddress extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    private IpAddress $ipAddress;

    public function __construct(Nic $nic, IpAddress $ipAddress)
    {
        $this->model = $nic;

        $this->ipAddress = $ipAddress;
    }

    /**
     * Patch a Tier-1 segment port with an IP address binding
     * @see: https://vdc-download.vmware.com/vmwb-repository/dcr-public/787988e9-6348-4b2a-8617-e6d672c690ee/a187360c-77d5-4c0c-92a8-8e07aa161a27/api_includes/method_PatchTier1SegmentPort.html
     * @return bool|void
     * @throws \Exception
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $nic = $this->model;

        $network = $nic->network;
        $router = $nic->network->router;
        $nsxService = $router->availabilityZone->nsxService();

        $nic->refresh();

        $ipAddresses = $nic->ipAddresses->where('type', IpAddress::TYPE_CLUSTER);
        $ipAddresses->push($this->ipAddress);

        $nsxService->patch(
            '/policy/api/v1/infra/tier-1s/' . $router->id .
            '/segments/' . $network->id .
            '/ports/' . $nic->id,
            [
                'json' => [
                    'resource_type' => 'SegmentPort',
                    'address_bindings' =>  $ipAddresses->values()->map(function ($ipAddress) use ($nic) {
                        return [
                            'ip_address' => $ipAddress->ip_address,
                            'mac_address' => $nic->mac_address
                        ];
                    })->toArray()
                ]
            ]
        );

        $nic->ipAddresses()->save($this->ipAddress);
        Log::info('Address binding created for ' . $nic->id . ' (' . $nic->mac_address . ') with IP ' . $this->ipAddress->ip_address);

        Log::info(get_class($this) . ' : Finished', ['id' => $nic->id]);
    }
}
