<?php

namespace App\Jobs\Tasks\Image;

use App\Jobs\Job;
use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Product;
use App\Traits\V2\LoggableModelJob;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UpdateBilling extends Job
{
    use Batchable, LoggableModelJob;

    private $instance;
    private Image $model;

    /**
     * UpdateBilling constructor.
     * @param Image $image
     * @param Instance|null $instance
     */
    public function __construct(Image $image, ?Instance $instance = null)
    {
        $this->model = $image;
        $this->instance = $instance;
    }

    public function handle()
    {
        $image = $this->model;

        $time = Carbon::now();

        $metaData = $image->imageMetadata()
            ->where('key', '=', 'ukfast.spec.volume.min')
            ->first();
        $volumeCapacity = (int)$metaData->value;

        $currentActiveMetric = BillingMetric::where('resource_id', $image->id)
            ->where('key', '=', 'image.private')
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            $currentActiveMetric->end = $time;
            $currentActiveMetric->save();
            // if the resource is being deleted, there's nothing more we can do beyond this point
            if (empty($this->instance)) {
                Log::info('Billing Metric ' . $currentActiveMetric->id . ' updated for deleted instance, nothing more to do.');
                return;
            }
        }
        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $image->id,
            'vpc_id' => $this->instance->vpc->id,
            'reseller_id' => $this->instance->vpc->reseller_id,
            'key' => 'image.private',
            'value' => $volumeCapacity,
            'start' => $time,
        ]);

        $productName = $this->instance->availabilityZone->id . ': volume-1gb';
        /** @var Product $product */
        $product = $this->instance->availabilityZone->products()
            ->where('product_name', 'LIKE', '%volume-1gb%')
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load "' . $productName . '" billing product for availability zone ' . $this->instance->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($this->instance->vpc->reseller_id);
        }

        $billingMetric->save();
    }
}
