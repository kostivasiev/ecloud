<?php

namespace App\Listeners\V2\BillingMetric;

use App\Models\V2\BillingMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class End
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        if ($event->model->id === null) {
            Log::info(get_class($this) . ' : Nothing to do, resource ID not found', ['event' => $event]);
            return true;
        }

        $billingMetric = BillingMetric::where('resource_id', $event->model->id)
            ->where('end', null);
        if (!$billingMetric) {
            Log::info(get_class($this) . ' : Nothing to do, no billing metric(s) for resource', ['event' => $event]);
            return true;
        }

        $billingMetric->each(function ($metric) use ($event) {
            $metric->end = Carbon::now();
            $metric->save();
            Log::info(get_class($this) . ' : Updated end on billing metric ' . $metric->id . ' for resource',
                ['event' => $event]);
        });

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
