<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Traits\V2\Listeners\BillableListener;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UpdateMsSqlLicenseBilling
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

        if ($instance->image->imagemetadata->where('key', 'ukfast.license.type')->count() == 0) {
            return;
        }

        $licenseType = $instance->image->imagemetadata->where('key', 'ukfast.license.type')->first()->value;
        if ($licenseType !== 'mssql') {
            return;
        }

        $edition = Str::replace(
            'datacenter-mssql2019-',
            '',
            $instance->image->imagemetadata->where('key', 'ukfast.license.mssql.edition')->first()->value
        );

        $key = 'license.' . $licenseType . '.' . $edition;
        $cores = $instance->vcpu_cores < 4 ? 4 : $instance->vcpu_cores;
        $packs = ceil($cores / 2);

        // Check for an associated license billing product, if we find one, we want to bill for this license.
        $product = $instance->availabilityZone->products()
            ->where('product_name', $instance->availabilityZone->id . ': mssql ' . $edition . ' license')
            ->first();
        if (!empty($product)) {
            $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'license.' . $licenseType . '.' . $edition);

            if (!empty($currentActiveMetric)) {
                if ($currentActiveMetric->value == $packs) {
                    return;
                }
                $currentActiveMetric->setEndDate();
            }

            $billingMetric = app()->make(BillingMetric::class);
            $billingMetric->fill([
                'resource_id' => $instance->id,
                'vpc_id' => $instance->vpc->id,
                'reseller_id' => $instance->vpc->reseller_id,
                'key' => $key,
                'value' => $packs,
                'start' => Carbon::now(),
                'category' => $product->category,
                'price' => $product->getPrice($instance->vpc->reseller_id) * $packs,
            ]);
            $billingMetric->save();

            Log::info('Billing metric ' . $key . ' added for resource ' . $instance->id);
        }
    }
}
