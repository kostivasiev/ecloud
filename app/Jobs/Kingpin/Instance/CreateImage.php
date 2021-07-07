<?php

namespace App\Jobs\Kingpin\Instance;

use App\Jobs\Job;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateImage extends Job
{
    use Batchable, LoggableModelJob;

    private Instance $instance;
    private Image $model;

    public function __construct(Image $image, Instance $instance)
    {
        $this->instance = $instance;
        $this->model = $image;
    }

    public function handle()
    {
        try {
            $response = $this->instance->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->instance->vpc->id . '/template/' . $this->model->id
            );

            if ($response->getStatusCode() == 200) {
                Log::debug(get_class($this) . ' : Image already exists, nothing to do.', ['id' => $this->instance->id]);
                return true;
            }
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
        }

        $this->instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/template',
            [
                'json' => [
                    'instanceId' => $this->instance->id,
                    'templateName' => $this->model->id,
                    'annotation' => $this->model->name,
                ]
            ]
        );
        Log::debug('Image ' . $this->model->id . ' has been created for instance ' . $this->instance->id);
    }
}
