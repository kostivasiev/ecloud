<?php

namespace App\Jobs\Kingpin\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteCluster extends Job
{
    use Batchable, LoggableModelJob;

    public $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $hostGroup = $this->model;
        $hostGroup->availabilityZone->kingpinService()->delete(
            '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id
        );
    }
}
