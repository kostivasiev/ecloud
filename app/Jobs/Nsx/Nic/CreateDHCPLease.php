<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\Job;
use App\Models\V2\Nic;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateDHCPLease extends Job
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

        $nsxService = $this->model->instance->availabilityZone->nsxService();

        $nsxService->put(
            '/policy/api/v1/infra/tier-1s/' . $this->model->network->router->id . '/segments/' . $this->model->network->id
            . '/dhcp-static-binding-configs/' . $this->model->id,
            [
                'json' => [
                    'resource_type' => 'DhcpV4StaticBindingConfig',
                    'mac_address' => $this->model->mac_address,
                    'ip_address' => $this->model->ip_address
                ]
            ]
        );

        Log::info('DHCP static binding created for ' . $this->model->id . ' (' . $this->model->mac_address . ') with IP ' . $this->model->ip_address);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
