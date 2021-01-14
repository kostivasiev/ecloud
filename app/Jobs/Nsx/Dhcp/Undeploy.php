<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(Dhcp $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        $this->model->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/dhcp-server-configs/' . $this->model->id
        );

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
