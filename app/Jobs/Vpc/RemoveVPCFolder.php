<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\AvailabilityZone;
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
            /** @var AvailabilityZone $availabilityZone */
            try {
                $availabilityZone->kingpinService()->delete('/api/v2/vpc/' . $this->model->id);
                Log::info('Deleting VPC folder.', ['id' => $this->model->id, 'availabilityZone' => $availabilityZone->name]);
            } catch (RequestException $exception) {
                if ($exception->getCode() != 404) {
                    throw $exception;
                }

                Log::error('VPC folder not found on availability zone, going to next.', [$exception]);
            }
        });
    }
}
