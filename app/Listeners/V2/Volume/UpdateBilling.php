<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Volume\Synced;
use App\Models\V2\BillingMetric;
use Carbon\Carbon;

class UpdateBilling
{
    /**
     * @param Synced $event
     * @return void
     * @throws \Exception
     */
    public function handle(Synced $event)
    {
        $volume = $event->model;

        $time = Carbon::now();

        $existingMetric = BillingMetric::forResource($volume)->first();
        if (!empty($existingMetric)) {
            $existingMetric->end = $time;
            $existingMetric->save();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $volume->getKey();
        $billingMetric->vpc_id = $volume->vpc->getKey();
        $billingMetric->reseller_id = $volume->vpc->reseller_id;
        $billingMetric->key = 'disk.capacity';
        $billingMetric->value = $volume->capacity;
        $billingMetric->start = $time;
        $billingMetric->save();
    }
}
