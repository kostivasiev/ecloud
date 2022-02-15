<?php
namespace App\Listeners\V2\Vpc;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Vpc;
use App\Traits\V2\Listeners\BillableListener;
use Illuminate\Support\Facades\Log;

class UpdateSupportEnabledBilling implements Billable
{
    use BillableListener;

    public static string $category = 'Support';

    const RESOURCE = Vpc::class;

    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        $this->validateBillableResourceEvent($event);

        $vpc = $event->model->resource;

        $currentActiveMetric = BillingMetric::getActiveByKey($vpc, self::getKeyName());

        if ($vpc->support_enabled === false && !empty($currentActiveMetric)) {
            $currentActiveMetric->setEndDate();
        } elseif ($vpc->support_enabled === true && empty($currentActiveMetric)) {
            $billingMetric = app()->make(BillingMetric::class);
            $billingMetric->resource_id = $vpc->id;
            $billingMetric->vpc_id = $vpc->id;
            $billingMetric->category = self::$category;
            $billingMetric->reseller_id = $vpc->reseller_id;
            $billingMetric->name = self::getFriendlyName();
            $billingMetric->key = self::getKeyName();
            $billingMetric->value = 1;
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
