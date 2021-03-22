<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use App\Models\V2\Nic;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RemoveDHCPLease extends Job
{
    use Batchable;

    private $nic;

    public function __construct(Nic $nic)
    {
        $this->nic = $nic;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->nic->id]);

        $nsxService = $this->nic->instance->availabilityZone->nsxService();

        /**
         * Create dhcp lease for the ip to the nic's mac address on NSX
         * @see https://185.197.63.88/policy/api_includes/method_CreateOrReplaceSegmentDhcpStaticBinding.html
         */
        try {
            $nsxService->put(
                '/policy/api/v1/infra/tier-1s/' . $this->nic->network->router->id . '/segments/' . $this->nic->network->id
                . '/dhcp-static-binding-configs/' . $this->nic->id,
                [
                    'json' => [
                        'resource_type' => 'DhcpV4StaticBindingConfig',
                        'mac_address' => $this->nic->mac_address,
                        'ip_address' => $this->nic->ip_address
                    ]
                ]
            );
        } catch (\Exception $exception) {
            if ($exception->hasResponse()) {
                Log::info(get_class($this), json_decode($exception->getResponse()->getBody()->getContents(), true));
            }
            throw $exception;
        }

        Log::info('DHCP static binding created for ' . $this->nic->id . ' (' . $this->nic->mac_address . ') with IP ' . $this->nic->ip_address);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->nic->id]);
    }
}
