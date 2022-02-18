<?php
namespace App\Jobs\Vpc;

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
    private $vpc;
    private $enable;

    public function __construct($vpc, $enable)
    {
        $this->vpc = $vpc;
        $this->enable = $enable;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->vpc->id]);

        $currentActiveMetric = BillingMetric::getActiveByKey($this->vpc, self::getKeyName());

        if ($this->enable === false && !empty($currentActiveMetric)) {
            $currentActiveMetric->setEndDate();
        } elseif ($this->enable === true && empty($currentActiveMetric)) {
            $billingMetric = app()->make(BillingMetric::class);
            $billingMetric->resource_id = $this->vpc->id;
            $billingMetric->vpc_id = $this->vpc->id;
            $billingMetric->category = self::$category;
            $billingMetric->reseller_id = $this->vpc->reseller_id;
            $billingMetric->name = self::getFriendlyName();
            $billingMetric->key = self::getKeyName();
            $billingMetric->value = 1;
            $billingMetric->save();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->vpc->id]);
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
