<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Sync;
use App\Traits\V2\InstanceOnlineState;
use App\Traits\V2\Listeners\BillableListener;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateRamBilling implements Billable
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
        if (!$this->validateNotDeletedResourceEvent($event)) {
            return;
        }

        $instance = $event->model->resource;

        if ($this->getOnlineStatus($instance)['online'] === false) {
            return;
        }

        if (!empty($instance->host_group_id)) {
            $instance->billingMetrics()
                ->whereIn('key', json_decode(self::getKeyName()))
                ->each(function ($billingMetric) use ($instance) {
                    $billingMetric->setEndDate();
                    Log::debug('End billing of `' . $billingMetric->key . '` for Instance ' . $instance->id);
                });
            Log::warning(
                get_class($this) . ': Instance ' . $instance->id . ' is in the host group ' .
                $instance->host_group_id . ', nothing to do'
            );
            return;
        }

        $currentActiveMetrics = BillingMetric::where('resource_id', $instance->id)
            ->whereNull('end')
            ->whereIn('key', json_decode(self::getKeyName()))
            ->get();

        if (!empty($currentActiveMetrics)) {
            if ($currentActiveMetrics->sum('value') == $instance->ram_capacity) {
                return;
            }
            $currentActiveMetrics->each(function ($metric) {
                $metric->setEndDate();
            });
        }

        $standardTierLimitMiB = config('billing.ram_tiers.standard') * 1024;

        // Standard tier billing
        $this->addBilling(
            $instance,
            json_decode(self::getKeyName())[0],
            ($instance->ram_capacity > $standardTierLimitMiB) ? $standardTierLimitMiB : $instance->ram_capacity,
            $instance->availabilityZone->id . ': ram-1mb'
        );

        // High RAM tier billing
        if ($instance->ram_capacity > $standardTierLimitMiB) {
            $this->addBilling(
                $instance,
                json_decode(self::getKeyName())[1],
                ($instance->ram_capacity - $standardTierLimitMiB),
                $instance->availabilityZone->id . ': ram:high-1mb'
            );
        }
    }

    private function addBilling($instance, $key, $value, $billingProduct)
    {
        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->id;
        $billingMetric->vpc_id = $instance->vpc->id;
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->name = self::getFriendlyName($key);
        $billingMetric->key = $key;
        $billingMetric->value = $value;
        $billingMetric->start = Carbon::now();

        $product = $instance->availabilityZone->products()->where('product_name', $billingProduct)->first();
        if (empty($product)) {
            Log::error(
                'Failed to load billing product ' . $billingProduct .' for availability zone ' . $instance->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);
        }

        $billingMetric->save();
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        $retVal = 'RAM';
        if (count(func_get_args()) > 0) {
            if (func_get_arg(0) == json_decode(self::getKeyName())[1]) {
                $retVal = 'High Capacity RAM';
            }
        }
        return $retVal;
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return json_encode(['ram.capacity', 'ram.capacity.high']);
    }
}
