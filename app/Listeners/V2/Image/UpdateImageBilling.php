<?php

namespace App\Listeners\V2\Image;

use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\Product;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateImageBilling
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);
        if ($event->model->name !== 'image_create' && $event->model->name !== Sync::TASK_NAME_DELETE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if (!$event->model->resource->vpc_id) {
            return;
        }

        $model = $event->model->resource;

        if (get_class($model) != Image::class) {
            return;
        }

        $time = Carbon::now();

        $metaData = $model->imageMetadata()
            ->where('key', '=', 'ukfast.spec.volume.min')
            ->first();
        $volumeCapacity = (int)$metaData->value;

        $currentActiveMetric = BillingMetric::where('resource_id', $model->id)
            ->where('key', '=', 'image.private')
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value === $volumeCapacity) {
                return;
            }
            $currentActiveMetric->setEndDate($time);
        }
        if ($event->model->name !== Sync::TASK_NAME_DELETE) {
            $billingMetric = app()->make(BillingMetric::class);
            $billingMetric->fill([
                'resource_id' => $model->id,
                'vpc_id' => $model->vpc_id,
                'reseller_id' => $model->vpc->reseller_id,
                'key' => 'image.private',
                'value' => $volumeCapacity,
                'start' => $time,
            ]);

            $availabilityZone = $model->availabilityZones()->first();
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
                $billingMetric->price = $product->getPrice($model->vpc->reseller_id);
            }

            $billingMetric->save();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
