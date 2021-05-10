<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;

class GuestShutdown extends Job
{
    use LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $this->model->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/power/guest/shutdown'
        );
    }
}
