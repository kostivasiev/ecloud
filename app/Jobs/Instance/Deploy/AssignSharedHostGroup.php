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
        if (!$hostGroup) {
            $message = get_class($this) . ' : A default hostgroup could not be found';
            Log::error($message, [
                'instance_id' => $this->model->id,
            ]);
            $this->fail(new \Exception($message));
            return;
        }

        $deployData = $this->model->deploy_data;
        $deployData['hostGroupId'] = $hostGroup->id;
        $this->model->deploy_data = $deployData;
        $this->model->save();
    }
}
