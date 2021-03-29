<?php

namespace App\Listeners\V2\HostGroup;

use App\Models\V2\BillingMetric;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use App\Models\V2\Sync;
use App\Support\Resource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model instanceof Sync) {
            if (!$event->model->completed
                || Resource::classFromId($event->model->resource_id) != HostGroup::class) {
                return;
            }

            $hostGroup = HostGroup::find($event->model->resource_id);

            if (!$hostGroup) {
                return;
            }

            if (!BillingMetric::getActiveByKey($hostGroup, 'hostgroup')) {
                $this->addBilling($hostGroup);;
            }
        }

        // Deleted host
        if ($event->model instanceof Host
            && $event->model->trashed()
            && $event->model->hostGroup->hosts->count() < 1
            && (!BillingMetric::getActiveByKey($event->model->hostGroup, 'hostgroup'))) {
            $this->addBilling($event->model->hostGroup);
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }

    public function addBilling(HostGroup $hostGroup): bool
    {
        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $hostGroup->id,
            'vpc_id' => $hostGroup->vpc->id,
            'reseller_id' => $hostGroup->vpc->reseller_id,
            'key' => 'hostgroup',
            'value' => 1,
            'start' => Carbon::now(),
            'category' => 'Compute',
        ]);

        $product = $hostGroup->availabilityZone
            ->products()
            ->where('product_name', $hostGroup->availabilityZone->id . ': hostgroup')
            ->first();

        if (empty($product)) {
            Log::error(
                'Failed to load \'hostgroup\' billing product for availability zone ' . $hostGroup->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($hostGroup->vpc->reseller_id);
        }

        return $billingMetric->save();
    }

//    public function endBilling(HostGroup $hostGroup): bool
//    {
//        $billingMetric = BillingMetric::getActiveByKey($hostGroup, 'hostgroup');
//        return $billingMetric->setEndDate();
//    }
}
