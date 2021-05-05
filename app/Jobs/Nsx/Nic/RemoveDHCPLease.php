<?php

namespace App\Jobs\Nsx\Nic;

use App\Jobs\Job;
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

        $nsxService = $this->model->instance->availabilityZone->nsxService();

        //Delete dhcp lease for the ip to the nic's mac address on NSX
        $nsxService->delete(
            '/policy/api/v1/infra/tier-1s/' . $this->model->network->router->id . '/segments/' . $this->model->network->id
            . '/dhcp-static-binding-configs/' . $this->model->id
        );

        Log::info('DHCP static binding deleted for ' . $this->model->id . ' (' . $this->model->mac_address . ') with IP ' . $this->model->ip_address);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
