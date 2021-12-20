<?php

namespace App\Listeners\V2\InstanceSoftware;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\InstanceSoftware;
use App\Traits\V2\Listeners\BillableListener;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateBilling implements Billable
{
    use BillableListener;

    const RESOURCE = InstanceSoftware::class;

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

        $instanceSoftware = $event->model->resource;
        $license = $instanceSoftware->software->license;
        $instance = $instanceSoftware->instance;

        // No license = no billing required
        if (empty($license)) {
            return;
        }

        $product = $instance->availabilityZone
            ->products()
            ->where('product_name', $instance->availabilityZone->id . ': software:' . $license)
            ->first();

        // No billing product = no billing required
        if (empty($product)) {
            Log::warning(get_class($this) . ' : No billing product found for software with license \'' . $license . '\'. No billing metric was added.');
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($instanceSoftware, self::getKeyName($license));
        if (!empty($currentActiveMetric)) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $instanceSoftware->id,
            'vpc_id' => $instance->vpc->id,
            'reseller_id' => $instance->vpc->reseller_id,
            'name' => self::getFriendlyName($license),
            'key' => self::getKeyName($license),
            'category' => $product->category,
            'price' => $product->getPrice($instance->vpc->reseller_id),
            'value' => 1,
        ]);
        $billingMetric->save();
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        return 'Software: ' . ucwords(func_get_arg(0));
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'software.' . Str::replace(' ', '-', func_get_arg(0));
    }
}
