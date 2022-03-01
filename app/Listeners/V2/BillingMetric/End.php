<?php

namespace App\Listeners\V2\BillingMetric;

use App\Models\V2\BillingMetric;
use Illuminate\Support\Facades\Log;

class End
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model->id === null) {
            Log::info(get_class($this) . ' : Nothing to do, resource ID not found', ['id' => $event->model->id]);
            return true;
        }

        $billingMetric = BillingMetric::where('resource_id', $event->model->id)
            ->where('end', null);
        if (!$billingMetric) {
            Log::info(get_class($this) . ' : Nothing to do, no billing metric(s) for resource', ['id' => $event->model->id]);
            return true;
        }

        $billingMetric->each(function ($metric) use ($event) {
            $metric->setEndDate();
            Log::info(get_class($this) . ' : Updated end on billing metric ' . $metric->id . ' for resource', ['id' => $event->model->id]);
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
