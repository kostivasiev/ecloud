<?php
namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class StartVcpuBilling extends Job
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

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $instance->id;
        $billingMetric->vpc_id = $instance->vpc->id;
        $billingMetric->reseller_id = $instance->vpc->reseller_id;
        $billingMetric->key = 'vcpu.count';
        $billingMetric->value = $instance->vcpu_cores;
        $billingMetric->start = Carbon::now();

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
