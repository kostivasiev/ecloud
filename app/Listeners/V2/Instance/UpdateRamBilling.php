<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Resource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateRamBilling
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

        $instance = Instance::find($event->model->resource_id);

        if (empty($instance)) {
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'ram.capacity');

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == $instance->ram_capacity) {
                return;
            }
            $currentActiveMetric->setEndDate($time);
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->id;
        $billingMetric->vpc_id = $instance->vpc->id;
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->key = 'ram.capacity';
        $billingMetric->value = $instance->ram_capacity;
        $billingMetric->start = $time;

        $product = $instance->availabilityZone->products()->get()->firstWhere('name', 'ram');
        if (empty($product)) {
            Log::error(
                'Failed to load \'ram\' billing product for availability zone ' . $instance->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);
        }

        $billingMetric->save();
    }
}
