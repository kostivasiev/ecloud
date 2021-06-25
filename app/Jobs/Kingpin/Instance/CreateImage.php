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

    private Instance $model;
    private Image $image;

    public function __construct(Instance $instance, Image $image)
    {
        $this->model = $instance;
        $this->image = $image;
    }

    public function handle()
    {
        try {
            $response = $this->model->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->model->vpc->id . '/template/' . $this->image->id
            );

            if ($response->getStatusCode() == 200) {
                Log::debug(get_class($this) . ' : Image already exists, nothing to do.', ['id' => $this->model->id]);
                return true;
            }
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
        }

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
