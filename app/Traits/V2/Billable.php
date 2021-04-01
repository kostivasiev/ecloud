<?php

namespace App\Traits\V2;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\BillingMetric;
use App\Models\V2\Vpc;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

trait Billable
{
    public array $billableMetrics;

    public function getActiveBilling(): Collection
    {
        return BillingMetric::where('resource_id', $this->id)
            ->whereNull('end')
            ->get();
    }

    public function updateBilling(Vpc $vpc, $key, $value = 1)
    {
        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $this->id,
            'vpc_id' => $vpc->id,
            'reseller_id' => $vpc->reseller_id,
            'key' => $key,
            'value' => $value,
            'start' => Carbon::now(),
        ]);

        $product = $this->hostGroup->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%' . $this->hostGroup->hostSpec->id)
            ->first();
        if (empty($product)) {
            Log::error(
                get_class($this) . ': Failed to load \'host spec\' billing product \'' . $this->hostGroup->hostSpec->id
                . '\' for availability zone ' . $this->hostGroup->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($this->hostGroup->vpc->reseller_id);
        }

        $billingMetric->save();
    }

    public function getProduct(AvailabilityZone $availabilityZone, $product)
    {
        $product = $this->hostGroup->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%' . $this->hostGroup->hostSpec->id)
            ->first();
    }

}
