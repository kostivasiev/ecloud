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

        if (empty($instance) || $instance->isManaged()) {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, self::getKeyName());

        if (!$instance->host_group_id && empty($currentActiveMetric)) {
            return;
        }

        if (!$instance->host_group_id && !empty($currentActiveMetric)) {
            $currentActiveMetric->setEndDate();
            Log::info(get_class($this) . ' : High CPU was disabled for instance', ['instance' => $instance->id]);
            return;
        }

        if ($instance->host_group_id != 'hg-high-cpu' && empty($currentActiveMetric)) {
            return;
        }

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == 1 && $instance->host_group_id == 'hg-high-cpu') {
                return;
            }
            $currentActiveMetric->setEndDate();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->id;
        $billingMetric->vpc_id = $instance->vpc->id;
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->name = self::getFriendlyName();
        $billingMetric->key = self::getKeyName();
        $billingMetric->value = 1;

        $productName = $instance->availabilityZone->id . ': ' . self::getKeyName();
        $product = $instance->availabilityZone
            ->products()
            ->where('product_name', $productName)
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load \'high.cpu\' billing product for availability zone ' . $instance->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = 1;
        }

        Log::info(get_class($this) . ' : high.cpu enabled.', ['instance' => $instance->id]);
        $billingMetric->save();
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        return 'High Cpu';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'high.cpu';
    }
}
