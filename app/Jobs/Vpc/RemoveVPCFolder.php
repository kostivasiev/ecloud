<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Jobs\TaskJob;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Vpc;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveVPCFolder extends TaskJob
{
    public function handle()
    {
        $vpc = $this->task->resource;

        $vpc->region->availabilityZones->each(function ($availabilityZone) use ($vpc) {
            /** @var AvailabilityZone $availabilityZone */
            try {
                $this->info('Deleting VPC folder on availability zone ' . $availabilityZone->id);
                $availabilityZone->kingpinService()->delete('/api/v2/vpc/' . $vpc->id);
            } catch (RequestException $exception) {
                if ($exception->getCode() != 404) {
                    throw $exception;
                }

                $this->info('VPC folder not found on availability zone ' . $availabilityZone->id);
            }
        });
    }
}
