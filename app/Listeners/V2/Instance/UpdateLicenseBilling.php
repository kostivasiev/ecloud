<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Traits\V2\Listeners\BillableListener;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateLicenseBilling
{
    use BillableListener;

    const RESOURCE = Instance::class;

    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        if (!$this->validateBillableResourceEvent($event)) {
            return;
        }

        $instance = $event->model->resource;

        if ($instance->image->imagemetadata->where('key', 'ukfast.license.type')->count() == 0) {
            return;
        }

        $licenseType = $instance->image->imagemetadata->where('key', 'ukfast.license.type')->first()->value;

        // Check for an associated license billing product, if we find one, we want to bill for this license.
        $product = $instance->availabilityZone->products()
            ->where('product_name', $instance->availabilityZone->id . ': ' . $licenseType . '-license')
            ->first();
        if (!empty($product)) {
            $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'license.' . $licenseType);

            if (!empty($currentActiveMetric)) {
                return;
            }

            $key = 'license.' . $licenseType;

            $billingMetric = app()->make(BillingMetric::class);
            $billingMetric->fill([
                'resource_id' => $instance->id,
                'vpc_id' => $instance->vpc->id,
                'reseller_id' => $instance->vpc->reseller_id,
                'key' => $key,
                'value' => 1,
                'start' => Carbon::now(),
                'category' => $product->category,
                'price' => $product->getPrice($instance->vpc->reseller_id),
            ]);
            $billingMetric->save();

            Log::info('Billing metric ' . $key . ' added for resource ' . $instance->id);
        }
    }
}
