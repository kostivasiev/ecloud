<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Sync;
use App\Traits\V2\Listeners\BillableListener;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateWindowsLicenseBilling implements Billable
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

        if ($instance->platform != 'Windows') {
            return;
        }

        if ($instance->hostGroup->isPrivate()) {
            $instance->billingMetrics()
                ->where('key', '=', self::getKeyName())
                ->each(function ($billingMetric) use ($instance) {
                    $billingMetric->setEndDate();
                    Log::debug('End billing of `' . $billingMetric->key . '` for Instance ' . $instance->id);
                });
            Log::warning(
                get_class($this) . ': Instance ' . $instance->id . ' is in private host group ' .
                $instance->host_group_id . ', nothing to do'
            );
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, self::getKeyName());
        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == $instance->vcpu_cores) {
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
        $billingMetric->value = $instance->vcpu_cores;
        $billingMetric->start = Carbon::now();

        $product = $instance->availabilityZone->products()
            ->where('product_name', $instance->availabilityZone->id . ': windows-os-license')
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load \'windows\' billing product for availability zone ' .
                $instance->availabilityZone->id
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
        return 'Windows License (per core)';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'license.windows';
    }
}
