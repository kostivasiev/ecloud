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
        $instance = $this->model;

        // Wait for instance to power off
        if ($instance->getOnlineAgentStatus()['online'] === true) {
            Log::debug('Instance pending shutdown, waiting ' . $this->backoff . ' seconds to retry.');
            $this->release($this->backoff);
        }

        $instance->billingMetrics()
            ->whereIn('key', ['ram.capacity', 'ram.capacity.high', 'vcpu.count'])
            ->each(function ($billingMetric) use ($instance) {
                Log::debug('End billing of `' . $billingMetric->key . '` for Instance ' . $instance->id);
                $billingMetric->setEndDate();
            });
    }
}
