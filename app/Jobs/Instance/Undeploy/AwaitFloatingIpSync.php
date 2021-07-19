<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class AwaitFloatingIpSync extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private Instance $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $this->model->nics()->each(function ($nic) {
            if ($nic->floatingIp()->exists()) {
                $this->awaitSyncableResources([$nic->floatingIp->id]);
            }
        });
    }
}
