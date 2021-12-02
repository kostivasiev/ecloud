<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RemoveVCEFolder extends Job
{
    use Batchable, LoggableModelJob;

    private Vpc $model;

    public function __construct(Vpc $vpc)
    {
        $this->model = $vpc;
    }

    /**
     * @return bool
     */
    public function handle()
    {
        try {
            $this->model->availabilityZone->kingpinService()->delete('/api/v2/vpc/' . $this->model->id);
        } catch (RequestException $exception) {
            Log::error('Delete VPC folder error', [$exception]);
        }

        Log::info('Deleting VPC folder', ['vpcId'=>$this->model->id]);
    }
}