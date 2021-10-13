<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Natable;
use App\Models\V2\Nic;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitNatSync extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private $model;

    private $resource;

    public function __construct(FloatingIp $floatingIp, $resource)
    {
        $this->model = $floatingIp;
        $this->resource = $resource;
    }

    public function handle()
    {
        if (!($this->resource instanceof Natable)) {
            Log::info(get_class($this) . ' : Resource is not a Natable resource, skipping');
            return;
        }

        $this->awaitSyncableResources([
            $this->model->sourceNat->id,
            $this->model->destinationNat->id
        ]);
    }
}
