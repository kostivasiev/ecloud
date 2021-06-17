<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Jobs\Instance\StartRamBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Sync;

class UpdateRamBilling
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

        $currentActiveMetrics = BillingMetric::where('resource_id', $instance->id)
            ->whereNull('end')
            ->whereIn('key', ['ram.capacity', 'ram.capacity.high'])
            ->get();

        if (!empty($currentActiveMetrics)) {
            if ($currentActiveMetrics->sum('value') == $instance->ram_capacity) {
                return;
            }
            $currentActiveMetrics->each(function ($metric) {
                $metric->setEndDate();
            });
        }
        dispatch(new StartRamBilling($instance));
    }
}
