<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
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
        if (!$event->model->completed) {
            return;
        }

        if (Resource::classFromId($event->model->resource_id) != Instance::class) {
            return;
        }

        $instance = Instance::findOrFail($event->model->resource_id);

        if ($instance->platform != 'Windows') {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'license.windows');
        if (!empty($currentActiveMetric)) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->getKey();
        $billingMetric->vpc_id = $instance->vpc->getKey();
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->key = 'license.windows';
        $billingMetric->value = 1;
        $billingMetric->start = $time;

        $product = $instance->availabilityZone->products()->get()->firstWhere('name', 'windows');
        if (empty($product)) {
            Log::error(
                'Failed to load \'windows\' billing product for availability zone ' . $instance->availabilityZone->getKey()
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);
        }

        $billingMetric->save();
    }
}
