<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Sync;
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

        if ($instance->loadBalancer()->exists()) {
            return;
        }

        if (!empty($instance->host_group_id)) {
            $instance->billingMetrics()
                ->where('key', '=', 'vcpu.count')
                ->each(function ($billingMetric) use ($instance) {
                    $billingMetric->setEndDate();
                    Log::debug('End billing of `' . $billingMetric->key . '` for Instance ' . $instance->id);
                });
            Log::warning(get_class($this) . ': Instance ' . $instance->id . ' is in the host group ' . $instance->host_group_id . ', nothing to do');
            return;
        }

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'vcpu.count');

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == $instance->vcpu_cores) {
                return;
            }
            $currentActiveMetric->setEndDate($time);
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->id;
        $billingMetric->vpc_id = $instance->vpc->id;
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->key = 'vcpu.count';
        $billingMetric->value = $instance->vcpu_cores;
        $billingMetric->start = $time;

        $product = $instance->availabilityZone->products()->get()->firstWhere('name', 'vcpu');
        if (empty($product)) {
            Log::error(
                'Failed to load \'vcpu\' billing product for availability zone ' . $instance->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);
        }

        $billingMetric->save();
    }
}
