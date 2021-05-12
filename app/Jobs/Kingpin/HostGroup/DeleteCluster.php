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
        try {
            $hostGroup->availabilityZone->kingpinService()->delete(
                '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id
            );
        } catch (\Exception $exception) {
            Log::info('Exception Code: ' . $exception->getCode());
            if ($exception->getCode() !== 404) {
                $this->fail($exception);
            }
            Log::warning(
                get_class($this) . ' : Failed to delete Host Group ' . $hostGroup->id . '. Host group was not found, skipping'
            );
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
