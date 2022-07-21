<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Jobs\Tasks\Instance\PowerOn;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Sync;
use App\Traits\V2\InstanceOnlineState;
use App\Traits\V2\Listeners\BillableListener;
use Illuminate\Support\Facades\Log;

class UpdateVcpuBilling implements Billable
{
    use InstanceOnlineState, BillableListener;

    const RESOURCE = Instance::class;

    const EVENTS = [
        Sync::TASK_NAME_UPDATE,
        PowerOn::TASK_NAME
    ];

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

        if ($instance->hostGroup->isPrivate()) {
            $instance->billingMetrics()
                ->where('key', '=', self::getKeyName())
                ->each(function ($billingMetric) use ($instance) {
                    $billingMetric->setEndDate();
                    Log::debug('End billing of `' . $billingMetric->key . '` for Instance ' . $instance->id);
                });
            Log::warning(
                get_class($this) . ': Instance ' . $instance->id . ' is in a private host group ' .
                $instance->host_group_id . ', nothing to do'
            );
            return;
        }

        if ($this->getOnlineStatus($instance)['online'] === false) {
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
        $billingMetric->name = $this->getFriendlyName();
        $billingMetric->key = self::getKeyName();
        $billingMetric->value = $instance->vcpu_cores;

        $product = $instance->availabilityZone->products()->get()->firstWhere('name', 'vcpu');
        if (empty($product)) {
            Log::error(
                'Failed to load \'vcpu\' billing product for availability zone ' .
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
        return 'VCPU Count';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'vcpu.count';
    }
}
