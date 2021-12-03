<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Traits\V2\Listeners\BillableListener;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use UKFast\Admin\Licenses\AdminClient;

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

        if ($instance->image->getMetadata('ukfast.license.type') != 'mssql') {
            return;
        }

        $licensesAdminClient = app()->make(AdminClient::class)->setResellerId($instance->vpc->reseller_id);
        $licenses = collect($licensesAdminClient->licenses()->getAll([
            'owner_id:eq' => $instance->id,
            'license_type:eq' => 'mssql',
        ]));

        if ($licenses->count() === 0) {
            return;
        }

        $license = $licenses[0]->keyId;
        $edition = Str::lower(Arr::last(Str::of($license)->explode('-')->toArray()));

        $key = 'license.mssql.' . $edition;
        $cores = $instance->vcpu_cores < 4 ? 4 : $instance->vcpu_cores;
        $packs = ceil($cores / 2);

        // Check for an associated license billing product, if we find one, we want to bill for this license.
        $product = $instance->availabilityZone->products()
            ->firstWhere('product_name', 'LIKE', $instance->availabilityZone->id . '%mssql%' . $edition . '%');
        if (!empty($product)) {
            $currentActiveMetric = BillingMetric::getActiveByKey($instance, 'license.mssql.' . $edition);

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
