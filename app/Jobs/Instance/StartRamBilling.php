<?php
namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class StartRamBilling extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;
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