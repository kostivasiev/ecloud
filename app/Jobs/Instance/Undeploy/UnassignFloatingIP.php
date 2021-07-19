<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UnassignFloatingIP extends Job
{
    use Batchable, LoggableModelJob;
    
    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;

        $instance->nics()->each(function ($nic) {
            if ($nic->floatingIp()->exists()) {
                $floatingIp = $nic->floatingIp;
                Log::info(get_class($this) . ' : Unassigning floating IP '. $floatingIp->id . ' for NIC ' . $nic->id);
                $floatingIp->resource()->dissociate();
                $floatingIp->syncSave();
            }
        });
    }
}
