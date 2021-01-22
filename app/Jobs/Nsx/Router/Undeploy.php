<?php

namespace App\Jobs\Nsx\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private $model;

    public function __construct(Router $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        # Delete Locale Rules associated with the router first, or the router won't delete
        $this->model->availabilityZone->nsxService()->delete('policy/api/v1/infra/tier-1s/'.$this->model->id.'/locale-services/'.$this->model->id);

        # Delete the router
        $this->model->availabilityZone->nsxService()->delete('policy/api/v1/infra/tier-1s/' . $this->model->id);

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
