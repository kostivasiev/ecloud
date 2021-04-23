<?php

namespace App\Listeners\V2\Vpc;

use App\Events\V2\Sync\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\FloatingIp;
use App\Models\V2\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateFloatingIpBilling
{
    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        if (!($event->model instanceof Sync)) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        $floatingIp = $event->model->resource;

        if (get_class($floatingIp) != FloatingIp::class) {
            return;
        }

        $currentActiveMetric = BillingMetric::getActiveByKey($floatingIp->vpc, 'floating-ip.count');

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value == $floatingIp->vpc->floatingIps()->count()) {
                return;
            }
            $currentActiveMetric->setEndDate();
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $floatingIp->vpc->id;
        $billingMetric->vpc_id = $floatingIp->vpc->id;
        $billingMetric->reseller_id = $floatingIp->vpc->reseller_id;
        $billingMetric->key = 'floating-ip.count';
        $billingMetric->value = $floatingIp->vpc->floatingIps()->count();
        $billingMetric->start = Carbon::now();

        // Bit of a hack to get the az product, as technically a fip isn't associated with an az
        $availabilityZone = $floatingIp->vpc->region->availabilityZones()->first();

        $product = $availabilityZone->products()->where('product_name', $availabilityZone->id . ': floating ip')->first();
        if (empty($product)) {
            Log::error(
                'Failed to load billing product ' . $availabilityZone->id . ': floating ip'
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($floatingIp->vpc->reseller_id);
        }

        $billingMetric->save();
    }
}
