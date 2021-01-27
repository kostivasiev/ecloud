<?php

namespace App\Jobs\VmWare\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private $model;

    public function __construct(Volume $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        if ($this->model->instances()->count() !== 0) {
            // TODO :- Move this to a deleation rule, it's not right doing it here?
            throw new \Exception('Volume ' . $this->model->id . ' had instances when trying to delete');
        }

        $this->model->availabilityZone->kingpinService()->delete(
            '/api/v1/vpc/' . $this->model->vpc->id . '/volume/' . $this->model->vmware_uuid
        );

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
