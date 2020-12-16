<?php

namespace App\Listeners\V2\BillingMetric;

use App\Models\V2\BillingMetric;
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

        $billingMetric = BillingMetric::where('resource_id', $event->model->id);
        if (!$billingMetric) {
            Log::info(get_class($this) . ' : Nothing to do, no billing metric for resource', ['event' => $event]);
            return true;
        }

        if (!$billingMetric->delete()) {
            Log::warning(get_class($this) . ' : Failed to delete billing metric for resource', ['event' => $event]);
            return false;
        }

        Log::info(get_class($this) . ' : Deleted billing metric for resource', ['event' => $event]);

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
