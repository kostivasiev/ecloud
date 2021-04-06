<?php

namespace App\Listeners\V2\Host;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Host;
use App\Models\V2\Instance;
use App\Models\V2\Sync;
use App\Support\Resource;
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
        $sync = $event->model;

        if (!$sync->completed
            || $sync->type != Sync::TYPE_UPDATE
            || !($sync->resource instanceof Host)
        ) {
            return;
        }

        $host = $sync->resource;

//        if ($instance->platform != 'Windows') {
//            return;
//        }
//
//        $time = Carbon::now();
//
//        $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'license.windows');
//        if (!empty($currentActiveMetric)) {
//            return;
//        }
//
//        $billingMetric = app()->make(BillingMetric::class);
//        $billingMetric->resource_id = $instance->id;
//        $billingMetric->vpc_id = $instance->vpc->id;
//        $billingMetric->reseller_id = $instance->vpc->reseller_id;
//        $billingMetric->key = 'license.windows';
//        $billingMetric->value = 1;
//        $billingMetric->start = $time;
//
//        $product = $instance->availabilityZone->products()->get()->firstWhere('name', 'windows');
//        if (empty($product)) {
//            Log::error(
//                'Failed to load \'windows\' billing product for availability zone ' . $instance->availabilityZone->id
//            );
//        } else {
//            $billingMetric->category = $product->category;
//            $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);
//        }
//
//        $billingMetric->save();
    }
}
