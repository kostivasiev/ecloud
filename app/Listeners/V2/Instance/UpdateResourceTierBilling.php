<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Traits\V2\InstanceOnlineState;
use App\Traits\V2\Listeners\BillableListener;
use Illuminate\Support\Facades\Log;

class UpdateResourceTierBilling implements Billable
{
    use InstanceOnlineState, BillableListener;

    const RESOURCE = Instance::class;

    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        $instance = $event->model->resource;
        if (!$this->validateBillableResourceEvent($event)) {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, self::getKeyName() . '%', 'LIKE');

        if ($currentActiveMetric?->key == self::getKeyName() . '.' . $instance->resource_tier_id) {
            return;
        }

        $productName = $instance->availabilityZone->id . ': ' . $instance->resource_tier_id;
        $product = $instance->availabilityZone
            ->products()
            ->where('product_name', $productName)
            ->first();

        if (!$product && $currentActiveMetric === null) {
            Log::info(get_class($this) . ': resource tier billing does not apply to this instance, skipping', [
                'instance' => $instance->id
            ]);
            return;
        }

        if (!$product && !empty($currentActiveMetric)) {
            $currentActiveMetric->setEndDate();
            Log::info(get_class($this) . ' : resource tier billing was disabled for instance', ['instance' => $instance->id]);
            return;
        }

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == 1 && !empty($product)) {
                return;
            }
            $currentActiveMetric->setEndDate();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->id;
        $billingMetric->vpc_id = $instance->vpc->id;
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->name = $product->product_description;
        $billingMetric->key = self::getKeyName() . '.' . $instance->resource_tier_id;
        $billingMetric->value = 1;

        $billingMetric->category = $product->category;
        $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);

        Log::info(get_class($this) . ' : ' . self::getKeyName() . ' enabled.', ['instance' => $instance->id]);
        $billingMetric->save();
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        return 'Resource Tier';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'resource.tier';
    }
}
