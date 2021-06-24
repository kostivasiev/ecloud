<?php

namespace App\Jobs\Kingpin\Instance;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateImage extends Job
{
    use Batchable, LoggableModelJob;

    private Instance $model;
    private Image $image;

    public function __construct(Instance $instance, Image $image)
    {
        $this->model = $instance;
        $this->image = $image;
    }

    public function handle()
    {
        $this->model->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->model->vpc->id . '/template',
            [
                'json' => [
                    'instanceId' => $this->model->id,
                    'templateName' => $this->image->id,
                    'annotation' => $this->image->name,
                ]
            ]
        );
        Log::debug('Image ' . $this->image->id . ' has been created for instance ' . $this->model->id);
    }
}
