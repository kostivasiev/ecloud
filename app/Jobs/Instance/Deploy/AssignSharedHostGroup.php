<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\HostSpec;
use App\Models\V2\Instance;
use App\Models\V2\ResourceTier;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AssignSharedHostGroup extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        if (!empty($this->model->host_group_id)) {
            Log::info(get_class($this) . ' : Host Group already assigned, skipping');
            return;
        }

        $hostGroup = $this->model->availabilityZone->getDefaultHostGroup();

        $this->model->deploy_data['hostGroupId'] = $hostGroup->id;
        $this->model->save();
    }
}
