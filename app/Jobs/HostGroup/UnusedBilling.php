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
        $metric = $this->model->availabilityZone->id . ': ' . $this->metric;

        $currentActiveMetric = BillingMetric::getActiveByKey($this->model, '%'.$this->metric.'%', 'LIKE');
        if (!empty($currentActiveMetric)) {
            if ($hostCount === 0) {
                return;
            }
            $currentActiveMetric->end = $time;
            $currentActiveMetric->save();
            $this->model->setSyncCompleted();
            return;
        }

        $billingMetric = app()->make(BillingMetric::class, [
            'attributes' => [
                'resource_id' => $this->model->id,
                'vpc_id' => $this->model->vpc->id,
                'reseller_id' => $this->model->vpc->reseller_id,
                'key' => $metric,
                'value' => $this->model->hostSpec->id,
                'start' => $time
            ]
        ]);

        $product = $this->model->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%'.$metric.'%')
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
        $this->model->setSyncCompleted();

        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);
    }
}
