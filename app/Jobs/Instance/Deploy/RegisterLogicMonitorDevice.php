<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RegisterLogicMonitorDevice extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;

        if (empty($this->model->deploy_data['floating_ip_id'])) {
            Log::info(get_class($this) . ' : No floating IP assigned to the instance, skipping');
            return;
        }


    }
}
