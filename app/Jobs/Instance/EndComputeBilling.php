<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Services\V2\KingpinService;
use App\Traits\V2\InstanceOnlineState;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class EndComputeBilling extends Job
{

    use Batchable, LoggableModelJob, AwaitTask, AwaitResources, InstanceOnlineState;

    public $tries = 60;
    public $backoff = 5;
    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;
        if (!($this->getOnlineStatus($instance)['online'])) {
            $instance->billingMetrics()
                ->whereIn('key', ['ram.capacity', 'ram.capacity.high', 'vcpu.count'])
                ->whereNull('end')
                ->each(function ($billingMetric) {
                    Log::debug('End billing of `' . $billingMetric->key . '` for Instance ' . $this->model->id);
                    $billingMetric->setEndDate();
                });
        }
    }
}
