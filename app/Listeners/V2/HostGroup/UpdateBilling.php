<?php

namespace App\Listeners\V2\HostGroup;

use App\Models\V2\BillingMetric;
use App\Models\V2\HostGroup;
use App\Models\V2\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateBilling
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        /** @var HostGroup $model */
        $model = $event->model;
        if ($model instanceof Sync) {
            if (!$model->completed) {
                return;
            }
            $model = HostGroup::find($event->model->resource_id);
        }

        if (!($model instanceof HostGroup)) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $model->id,
            'vpc_id' => $model->vpc->id,
            'reseller_id' => $model->vpc->reseller_id,
            'key' => 'hostgroup',
            'value' => 1,
            'start' => Carbon::now(),
            'category' => 'Compute',
            'price' => 0,
        ]);
        $billingMetric->save();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
