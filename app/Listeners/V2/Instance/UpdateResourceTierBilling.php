<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\ResourceTier;
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

        $usedCpu = $this->getResourceTierUsage($instance);
        $currentActiveMetric = BillingMetric::getActiveByKey($instance, self::getKeyName() . '%', 'LIKE');

        if ($currentActiveMetric?->key == self::getKeyName() . '.' . $instance->resource_tier_id && $currentActiveMetric->value === $usedCpu) {
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

        // End the old metric, if there has been a change in CPU usage
        if (!empty($currentActiveMetric) && $currentActiveMetric->value !== $usedCpu) {
            $currentActiveMetric->setEndDate();
            Log::info(get_class($this) . ' : current cpu usage has changed, ending old metric', ['instance' => $instance->id]);
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->id;
        $billingMetric->vpc_id = $instance->vpc->id;
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->name = $product->product_description;
        $billingMetric->key = self::getKeyName() . '.' . $instance->resource_tier_id;
        $billingMetric->value = $usedCpu;

        $billingMetric->category = $product->category;
        $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);

        Log::info(get_class($this) . ' : ' . self::getKeyName() . ' enabled.', ['instance' => $instance->id]);
        $billingMetric->save();
    }

    /**
     * Gets the number of vcpu in use by all hostgroups in tier
     * @param Instance $instance
     * @return int
     */
    public function getResourceTierUsage(Instance $instance): int
    {
        $cpuTotal = 0;
        $resourceTier = ResourceTier::find($instance->resource_tier_id);
        if ($resourceTier !== null) {
            foreach ($resourceTier->hostGroups as $hostGroup) {
                $cpuTotal += $hostGroup->vcpu_used;
            }
        }
        return $cpuTotal;
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
