<?php
namespace App\Jobs\HostGroup;

use App\Models\V2\BillingMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UnusedBilling
{
    public $metric = 'hostgroup.unallocated';
    public $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $time = Carbon::now();
        $hostCount = $this->model->hosts()->count();
        if ($hostCount > 0) {
            $currentActiveMetric = BillingMetric::getActiveByKey($this->model, $this->metric . '%', 'LIKE');
            if (!empty($currentActiveMetric)) {
                if (($currentActiveMetric->value == $this->model->capacity) &&
                    ($currentActiveMetric->key == $this->metric)) {
                    return;
                }
                $currentActiveMetric->end = $time;
                $currentActiveMetric->save();
            }
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->resource_id = $this->model->id;
        $billingMetric->vpc_id = $this->model->vpc->id;
        $billingMetric->reseller_id = $this->model->vpc->reseller_id;
        $billingMetric->key = $this->metric;
        $billingMetric->value = $this->model->hostSpec->id;
        $billingMetric->start = $time;

        $product = $this->model->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%hostgroup.unallocated')
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load \'hostgroup\' billing product for availability zone ' . $this->model->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($this->model->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);
    }
}
