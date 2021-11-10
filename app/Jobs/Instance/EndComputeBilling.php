<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class EndComputeBilling extends Job
{

    use Batchable, LoggableModelJob, AwaitTask, AwaitResources;

    public $tries = 60;
    public $backoff = 5;
    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        if (!$this->model->is_online) {
            $this->model->billingMetrics()
                ->whereIn('key', ['ram.capacity', 'ram.capacity.high', 'vcpu.count'])
                ->each(function ($billingMetric) {
                    Log::debug('End billing of `' . $billingMetric->key . '` for Instance ' . $this->model->id);
                    $billingMetric->setEndDate();
                });
        }
    }
}
