<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Jobs\Instance\StartWindowsBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Resource;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateLicenseBilling
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

        if (get_class($event->model->resource) != Instance::class) {
            return;
        }

        $instance = $event->model->resource;

        if (empty($instance)) {
            return;
        }

        if ($instance->platform != 'Windows') {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'license.windows');
        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == $instance->vcpu_cores) {
                return;
            }
            $currentActiveMetric->setEndDate();
        }
        dispatch(new StartWindowsBilling($instance));
    }
}
