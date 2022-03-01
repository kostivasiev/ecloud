<?php

namespace App\Listeners\V2\Image;

use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateImageBilling implements Billable
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);
        if ($event->model->name !== 'image_create') {
            Log::info(get_class($this) . ' : Model name was not image_create, skipping');
            return;
        }

        if (!$event->model->completed) {
            Log::info(get_class($this) . ' : model was not completed, skipping');
            return;
        }

        if (!$event->model->resource->vpc_id) {
            Log::info(get_class($this) . ' : vpc_id was not set, skipping');
            return;
        }

        $model = $event->model->resource;

        if (get_class($model) === Instance::class) {
            if (is_null($event->model->data) || !array_key_exists('image_id', $event->model->data)) {
                Log::info(get_class($this) . ' : no data found, skipping');
                return;
            }
            $model = Image::find($event->model->data['image_id']);
        }

        if (get_class($model) != Image::class) {
            Log::info(get_class($this) . ' : Image class not found, skipping');
            return;
        }

        $metaData = $model->imageMetadata()
            ->where('key', '=', 'ukfast.spec.volume.min')
            ->first();
        $volumeCapacity = (int)$metaData->value;

        $currentActiveMetric = BillingMetric::where('resource_id', $model->id)
            ->where('key', '=', self::getKeyName())
            ->whereNull('end')
            ->first();

        if (!empty($currentActiveMetric)) {
            if ($currentActiveMetric->value === $volumeCapacity) {
                return;
            }
            $currentActiveMetric->setEndDate();
        }
        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $model->id,
            'vpc_id' => $model->vpc_id,
            'reseller_id' => $model->vpc->reseller_id,
            'name' => self::getFriendlyName(),
            'key' => self::getKeyName(),
            'value' => $volumeCapacity,
        ]);

        $availabilityZone = $model->availabilityZones()->first();
        $productName = $availabilityZone->id . ': volume-1gb';
        /** @var Product $product */
        $product = $availabilityZone->products()
            ->where('product_name', 'LIKE', '%volume-1gb%')
            ->first();
        if (empty($product)) {
            Log::error(
                'Failed to load "' . $productName . '" billing product for availability zone '.
                $availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($model->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        return 'Image per Gb';
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        return 'image.private';
    }
}
