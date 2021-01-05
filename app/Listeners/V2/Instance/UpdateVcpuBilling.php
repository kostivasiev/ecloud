<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Resource;
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
        if (!$event->model->completed) {
            return;
        }

        if (Resource::classFromId($event->model->resource_id) != Instance::class) {
            return;
        }

        $instance = Instance::findOrFail($event->model->resource_id);

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'vcpu.count');

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == $instance->vcpu_cores) {
                return;
            }
            $currentActiveMetric->setEndDate($time);
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->getKey();
        $billingMetric->vpc_id = $instance->vpc->getKey();
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->key = 'vcpu.count';
        $billingMetric->value = $instance->vcpu_cores;
        $billingMetric->start = $time;

        $product = $instance->availabilityZone->products()->get()->firstWhere('name', 'vcpu');
        if (empty($product)) {
            Log::error(
                'Failed to load \'vcpu\' billing product for availability zone ' . $instance->availabilityZone->getKey()
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);
        }

        $billingMetric->save();
    }
}
