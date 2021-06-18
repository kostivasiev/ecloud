<?php

namespace App\Jobs\Kingpin\Image;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteImage extends Job
{
    use Batchable, LoggableModelJob;

    public $model;

    public function __construct(Image $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $image = $this->model;
        if ($image->availabilityZones()->count() <= 0) {
            Log::warning(
                get_class($this) . ' : No availability zones found for Image ' . $image->id . ', skipping'
            );
            return;
        }

        $image->availabilityZones()->each(function ($availabilityZone) use ($image) {
            try {
                $availabilityZone->kingpinService()->delete(
                    '/api/v2/vpc/' . $image->vpc_id . '/template/' . $image->id
                );
            } catch (\Exception $exception) {
                Log::info('Exception Code: ' . $exception->getCode());
                if ($exception->getCode() !== 404) {
                    $this->fail($exception);
                    return;
                }
                Log::warning(
                    get_class($this) . ' : Failed to delete Image ' . $image->id . ' in az:' . $availabilityZone->id . '. Image was not found, skipping'
                );
                return;
            }
        });
    }
}
