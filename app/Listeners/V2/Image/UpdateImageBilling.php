<?php

namespace App\Listeners\V2\Image;

use App\Events\V2\Task\Updated;
use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\Product;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateImageBilling
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

        if (!$event->model->resource->vpc_id) {
            return;
        }

        $image = $event->model->resource;
        $availabilityZone = ($image->vpc->instances()->first())->availabilityZone;

        if (get_class($image) != Image::class) {
            return;
        }

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
            if ($currentActiveMetric->value === $volumeCapacity) {
                return;
            }
            $currentActiveMetric->setEndDate($time);
        }
        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $image->id,
            'vpc_id' => $image->vpc_id,
            'reseller_id' => $image->vpc->reseller_id,
            'key' => 'image.private',
            'value' => $volumeCapacity,
            'start' => $time,
        ]);

        $productName = $availabilityZone->id . ': volume-1gb';
        /** @var Product $product */
        $product = $availabilityZone->products()
            ->where('product_name', 'LIKE', '%volume-1gb%')
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load "' . $productName . '" billing product for availability zone ' . $availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($image->vpc->reseller_id);
        }

        $billingMetric->save();
    }
}
