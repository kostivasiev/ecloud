<?php

namespace App\Jobs\Image;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class SyncAvailabilityZones extends Job
{
    use Batchable, LoggableModelJob;

    public $model;

    public function __construct(Image $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $this->model->availabilityZones()->sync([]);
    }
}