<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Support\Resource;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateLicenseBilling
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if (get_class($event->model->resource) != Instance::class) {
            return;
        }

        $instance = $event->model->resource;

        if (empty($instance)) {
            return;
        }

        if ($instance->platform != 'Windows') {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'license.windows');
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
        $billingMetric->key = 'license.windows';
        $billingMetric->value = $instance->vcpu_cores;
        $billingMetric->start = Carbon::now();

        $product = $instance->availabilityZone->products()
            ->where('product_name', $instance->availabilityZone->id . ': windows-os-license')
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load \'windows\' billing product for availability zone ' . $instance->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($instance->vpc->reseller_id);
        }

        $billingMetric->save();
    }
}
