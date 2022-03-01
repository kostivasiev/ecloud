<?php
namespace App\Listeners\V2\Host;

use App\Events\V2\Task\Updated;
use App\Listeners\V2\Billable;
use App\Models\V2\BillingMetric;
use App\Models\V2\Host;
use App\Models\V2\HostSpec;
use App\Support\Sync;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateBilling
 * Add a billing metric when a dedicated host is created
 * @package App\Listeners\V2\Host
 */
class UpdateBilling implements Billable
{
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        $sync = $event->model;

        if ($event->model->name !== Sync::TASK_NAME_UPDATE) {
            return;
        }

        if (!$event->model->completed) {
            return;
        }

        if (!($sync->resource instanceof Host)) {
            return;
        }

        $host = $sync->resource;

        $currentActiveMetric = BillingMetric::getActiveByKey($host, $host->hostGroup->hostSpec->id);
        if (!empty($currentActiveMetric)) {
            return;
        }

        $billingMetric = app()->make(BillingMetric::class);
        $billingMetric->fill([
            'resource_id' => $host->id,
            'vpc_id' => $host->hostGroup->vpc->id,
            'reseller_id' => $host->hostGroup->vpc->reseller_id,
            'name' => self::getFriendlyName($host->hostGroup->hostSpec->id),
            'key' => self::getKeyName($host->hostGroup->hostSpec->id),
            'value' => 1,
            'start' => Carbon::now(),
            'category' => 'Compute',
        ]);

        $product = $host->hostGroup->availabilityZone
            ->products()
            ->where('product_name', 'LIKE', '%' . $host->hostGroup->hostSpec->id)
            ->first();
        if (empty($product)) {
            Log::error(
                get_class($this) . ': Failed to load \'host spec\' billing product \'' . $host->hostGroup->hostSpec->id
                . '\' for availability zone ' . $host->hostGroup->availabilityZone->id
            );
        } else {
            $billingMetric->category = $product->category;
            $billingMetric->price = $product->getPrice($host->hostGroup->vpc->reseller_id);
        }

        $billingMetric->save();

        Log::debug(get_class($this) . ': Added billing metric for ' . $host->id);

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }

    /**
     * Gets the friendly name for the billing metric
     * @return string
     */
    public static function getFriendlyName(): string
    {
        $argument = (count(func_get_args()) > 0) ? func_get_arg(0) : '';
        /** @var HostSpec $hostSpec */
        $hostSpec = HostSpec::withTrashed()->findOrFail($argument);
        return sprintf(
            'Host %d x %s, %d cores, %d GHz, %dGb RAM',
            $hostSpec->cpu_sockets,
            $hostSpec->cpu_type,
            $hostSpec->cpu_cores,
            $hostSpec->cpu_clock_speed,
            $hostSpec->ram_capacity
        );
    }

    /**
     * Gets the billing metric key
     * @return string
     */
    public static function getKeyName(): string
    {
        $argument = (count(func_get_args()) > 0) ? func_get_arg(0) : '';
        return sprintf('host.%s', $argument);
    }
}
