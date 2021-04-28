<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\Job;
use App\Models\V2\Nic;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateDHCPLease extends Job
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

        Log::info('DHCP static binding created for ' . $this->nic->id . ' (' . $this->nic->mac_address . ') with IP ' . $this->nic->ip_address);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->nic->id]);
    }
}
