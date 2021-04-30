<?php

namespace App\Jobs\Kingpin\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteCluster extends Job
{
    use Batchable;

    public $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $hostGroup = $this->model;
        $hostGroup->availabilityZone->kingpinService()->delete(
            '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
