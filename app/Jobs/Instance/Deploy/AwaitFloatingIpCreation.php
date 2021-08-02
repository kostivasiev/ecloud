<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitFloatingIpCreation extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        if ($this->model->deploy_data['requires_floating_ip'] !== true) {
            Log::info(get_class($this) . ' : Floating IP creation is not required, skipping');
            return;
        }

        if (empty($this->model->deploy_data['floating_ip_id'])) {
            $this->fail(new Exception('AwaitFloatingIpCreation for ' . $this->model->id . ': Failed. No floating_ip_id in deploy data'));
            return;
        }

        $this->awaitSyncableResources([$this->model->deploy_data['floating_ip_id']]);
    }
}
