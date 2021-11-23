<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Sync;
use App\Traits\V2\InstanceOnlineState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateRamBilling
{
    use InstanceOnlineState;
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        if ($event->model->name == Sync::TASK_NAME_DELETE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        $instance = $event->model->resource;

        if (get_class($instance) != Instance::class) {
            return;
        }

        if ($instance->isManaged()) {
            return;
        }

        if ($this->getOnlineStatus($instance)['online'] === false) {
            return;
        }

        if (!empty($instance->host_group_id)) {
            $instance->billingMetrics()
                ->whereIn('key', ['ram.capacity', 'ram.capacity.high'])
                ->each(function ($billingMetric) use ($instance) {
                    $billingMetric->setEndDate();
                    Log::debug('End billing of `' . $billingMetric->key . '` for Instance ' . $instance->id);
                });
            Log::warning(get_class($this) . ': Instance ' . $instance->id . ' is in the host group ' . $instance->host_group_id . ', nothing to do');
            return;
        }

        $currentActiveMetrics = BillingMetric::where('resource_id', $instance->id)
            ->whereNull('end')
            ->whereIn('key', ['ram.capacity', 'ram.capacity.high'])
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
            'ram.capacity',
            ($instance->ram_capacity > $standardTierLimitMiB) ? $standardTierLimitMiB : $instance->ram_capacity,
            $instance->availabilityZone->id . ': ram-1mb'
        );

        // High RAM tier billing
        if ($instance->ram_capacity > $standardTierLimitMiB) {
            $this->addBilling(
                $instance,
                'ram.capacity.high',
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
}
