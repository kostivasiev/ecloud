<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Vpc;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveVPCFolder extends Job
{
    use Batchable, LoggableModelJob, AwaitResources, AwaitTask;

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
        $availabilityZones = $this->model->region->availabilityZones;
        $availabilityZones->each(function ($availabilityZone) {
            try {
                $availabilityZone->kingpinService()->delete('/api/v2/vpc/' . $this->model->id);
            } catch (RequestException $exception) {
                if ($exception->getCode() != 404) {
                    throw $exception;
                }

                Log::error('VPC folder not found on availability zone, going next.', [$exception]);
            }
        });


        Log::info('Deleting VPC folder', ['vpcId'=>$this->model->id, 'count'=>$availabilityZones->count()]);
    }
}
