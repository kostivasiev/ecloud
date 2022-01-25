<?php
namespace App\Listeners\V2\Vpc;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateSupportEnabledBilling implements Billable
{
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if (!($event->model instanceof Task) || !($event->model->resource instanceof Instance)) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if ($event->model->resource->isManaged()) {
            return;
        }

        $vpc = $event->model->resource->vpc;

        $time = Carbon::now();

        $currentActiveMetric = BillingMetric::getActiveByKey($vpc, self::getKeyName());

        if ($vpc->support_enabled === false && !empty($currentActiveMetric)) {
            $currentActiveMetric->setEndDate($time);
        } elseif ($vpc->support_enabled === true && empty($currentActiveMetric)) {
            $billingMetric = app()->make(BillingMetric::class);
            $billingMetric->resource_id = $vpc->id;
            $billingMetric->vpc_id = $vpc->id;
            $billingMetric->reseller_id = $vpc->reseller_id;
            $billingMetric->name = self::getFriendlyName();
            $billingMetric->key = self::getKeyName();
            $billingMetric->value = 1;
            $billingMetric->start = $date ?? Carbon::now(new \DateTimeZone(config('app.timezone')));
            $billingMetric->save();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }

    /**
     * @return string
     */
    public static function getFriendlyName(): string
    {
        return "VPC Support";
    }

    /**
     * @return string
     */
    public static function getKeyName(): string
    {
        return "vpc.support";
    }
}
