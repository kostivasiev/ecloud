<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Jobs\Instance\StartVcpuBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateVcpuBilling
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        $instance = $event->model->resource;

        if (get_class($instance) != Instance::class) {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'vcpu.count');

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == $instance->vcpu_cores) {
                return;
            }
            $currentActiveMetric->setEndDate($time);
        }
        dispatch(new StartVcpuBilling($instance));
    }
}
